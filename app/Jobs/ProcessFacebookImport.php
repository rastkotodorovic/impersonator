<?php

namespace App\Jobs;

use App\Models\FacebookImportRun;
use App\Services\FacebookMessageImportService;
use App\Services\MessageEmbeddingService;
use App\Services\WhatsappChatImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProcessFacebookImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public int $importRunId,
    ) {}

    public function handle(
        FacebookMessageImportService $importService,
        MessageEmbeddingService $embeddingService,
        WhatsappChatImportService $whatsappImportService,
    ): void {
        $importRun = FacebookImportRun::findOrFail($this->importRunId);
        $tempDir = storage_path('app/tmp/facebook-imports/'.$importRun->id.'-'.uniqid());

        $importRun->update([
            'status' => 'processing',
            'started_at' => now(),
            'error' => null,
        ]);

        try {
            $importSource = $this->resolveImportSource($importRun->storage_path, $tempDir);

            if ($importRun->replace_existing) {
                $this->replaceExistingHistory();
            }

            $importResult = $importSource['type'] === 'whatsapp'
                ? $whatsappImportService->importFromPath($importSource['path'], $importRun->me_name)
                : $importService->importFromPath($importSource['path'], $importRun->me_name);

            $embeddingService->generate(true);

            $importRun->update([
                'status' => 'completed',
                'messages_imported' => $importResult['messages_imported'],
                'messages_skipped' => $importResult['messages_skipped'],
                'conversations_count' => $importResult['conversations'],
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $importRun->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        } finally {
            if (! str_starts_with($importRun->storage_path, 'local://') && Storage::disk('local')->exists($importRun->storage_path)) {
                Storage::disk('local')->delete($importRun->storage_path);
            }

            File::deleteDirectory($tempDir);
        }
    }

    protected function resolveImportSource(string $storagePath, string $tempDir): array
    {
        if (str_starts_with($storagePath, 'local://')) {
            $sourcePath = substr($storagePath, strlen('local://'));

            if (is_dir($sourcePath)) {
                return $this->locateImportSource($sourcePath);
            }

            if (is_file($sourcePath) && str_ends_with(strtolower($sourcePath), '.zip')) {
                return $this->extractArchive($sourcePath, $tempDir);
            }

            if ($this->isValidWhatsappExport($sourcePath)) {
                return [
                    'type' => 'whatsapp',
                    'path' => $sourcePath,
                ];
            }

            throw new \RuntimeException('The local import path does not exist or is not a supported Facebook, Instagram, or WhatsApp export.');
        }

        $uploadedPath = Storage::disk('local')->path($storagePath);

        if ($this->isValidWhatsappExport($uploadedPath)) {
            return [
                'type' => 'whatsapp',
                'path' => $uploadedPath,
            ];
        }

        return $this->extractArchive($uploadedPath, $tempDir);
    }

    protected function extractArchive(string $archivePath, string $destination): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('PHP ZipArchive extension is required for ZIP imports.');
        }

        File::ensureDirectoryExists($destination);

        $zip = new \ZipArchive;
        $result = $zip->open($archivePath);

        if ($result !== true) {
            throw new \RuntimeException('Unable to open the uploaded export archive.');
        }

        $zip->extractTo($destination);
        $zip->close();

        return $this->locateImportSource($destination);
    }

    protected function locateImportSource(string $path): array
    {
        if ($this->isValidMessagesDirectory($path)) {
            return [
                'type' => 'facebook',
                'path' => $path,
            ];
        }

        $messagesDirectory = $this->locateMessagesDirectory($path, false);

        if ($messagesDirectory !== null) {
            return [
                'type' => 'facebook',
                'path' => $messagesDirectory,
            ];
        }

        $whatsappExport = $this->locateWhatsappExport($path);

        if ($whatsappExport !== null) {
            return [
                'type' => 'whatsapp',
                'path' => $whatsappExport,
            ];
        }

        throw new \RuntimeException('The uploaded export does not contain a supported Facebook, Instagram, or WhatsApp messages file.');
    }

    protected function locateMessagesDirectory(string $destination, bool $throwOnFailure = true): ?string
    {
        $candidates = [
            $destination.'/your_facebook_activity/messages',
            $destination.'/your_instagram_activity/messages',
            $destination.'/messages',
        ];

        foreach ($candidates as $candidate) {
            if ($this->isValidMessagesDirectory($candidate)) {
                return $candidate;
            }
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($destination, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (! $item->isDir()) {
                continue;
            }

            $path = $item->getPathname();

            if ($this->isValidMessagesDirectory($path)) {
                return $path;
            }
        }

        if ($throwOnFailure) {
            throw new \RuntimeException('The uploaded archive does not contain a valid Facebook or Instagram messages export.');
        }

        return null;
    }

    protected function isValidMessagesDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }

        foreach (['inbox', 'e2ee_cutover', 'message_requests'] as $directory) {
            if (is_dir($path.'/'.$directory)) {
                return true;
            }
        }

        return false;
    }

    protected function locateWhatsappExport(string $path): ?string
    {
        if ($this->isValidWhatsappExport($path)) {
            return $path;
        }

        if (! is_dir($path)) {
            return null;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (! $item->isFile()) {
                continue;
            }

            if ($this->isValidWhatsappExport($item->getPathname())) {
                return $item->getPathname();
            }
        }

        return null;
    }

    protected function isValidWhatsappExport(string $path): bool
    {
        if (! is_file($path) || ! str_ends_with(strtolower($path), '.txt')) {
            return false;
        }

        $contents = file_get_contents($path, false, null, 0, 4096);

        if ($contents === false) {
            return false;
        }

        return preg_match('/^(\[[^\]]+\]\s.+?:\s|\d{1,2}[\/.]\d{1,2}[\/.]\d{2,4}.*,\s.*\s-\s.+?:\s)/mu', $contents) === 1;
    }

    protected function replaceExistingHistory(): void
    {
        DB::transaction(function () {
            DB::table('messages')->delete();
            DB::table('conversations')->delete();
        });
    }
}
