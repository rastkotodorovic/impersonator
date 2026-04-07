<?php

namespace App\Jobs;

use App\Models\FacebookImportRun;
use App\Services\FacebookMessageImportService;
use App\Services\MessageEmbeddingService;
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
    ): void {
        $importRun = FacebookImportRun::findOrFail($this->importRunId);
        $tempDir = storage_path('app/tmp/facebook-imports/'.$importRun->id.'-'.uniqid());

        $importRun->update([
            'status' => 'processing',
            'started_at' => now(),
            'error' => null,
        ]);

        try {
            $messagesPath = $this->resolveMessagesPath($importRun->storage_path, $tempDir);

            if ($importRun->replace_existing) {
                $this->replaceExistingHistory();
            }

            $importResult = $importService->importFromPath($messagesPath, $importRun->me_name);
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

    protected function resolveMessagesPath(string $storagePath, string $tempDir): string
    {
        if (str_starts_with($storagePath, 'local://')) {
            $sourcePath = substr($storagePath, strlen('local://'));

            if (is_dir($sourcePath)) {
                if ($this->isValidMessagesDirectory($sourcePath)) {
                    return $sourcePath;
                }

                return $this->locateMessagesDirectory($sourcePath);
            }

            if (is_file($sourcePath) && str_ends_with(strtolower($sourcePath), '.zip')) {
                return $this->extractArchive($sourcePath, $tempDir);
            }

            throw new \RuntimeException('The local Facebook import path does not exist or is not a ZIP / extracted messages directory.');
        }

        $archivePath = Storage::disk('local')->path($storagePath);

        return $this->extractArchive($archivePath, $tempDir);
    }

    protected function extractArchive(string $archivePath, string $destination): string
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('PHP ZipArchive extension is required for Facebook imports.');
        }

        File::ensureDirectoryExists($destination);

        $zip = new \ZipArchive;
        $result = $zip->open($archivePath);

        if ($result !== true) {
            throw new \RuntimeException('Unable to open uploaded Facebook export archive.');
        }

        $zip->extractTo($destination);
        $zip->close();

        return $this->locateMessagesDirectory($destination);
    }

    protected function locateMessagesDirectory(string $destination): string
    {
        $candidates = [
            $destination.'/your_facebook_activity/messages',
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

        throw new \RuntimeException('The uploaded archive does not contain a valid Facebook messages export.');
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

    protected function replaceExistingHistory(): void
    {
        DB::transaction(function () {
            DB::table('messages')->delete();
            DB::table('conversations')->delete();
        });
    }
}
