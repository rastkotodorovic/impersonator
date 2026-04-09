<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class MessageImportService
{
    public function __construct(
        protected JsonMessageImportService $jsonMessageImportService,
        protected WhatsappChatImportService $whatsappChatImportService,
    ) {}

    public function importFromPath(string $path, string $meName): array
    {
        $tempDir = null;

        try {
            if (is_file($path) && str_ends_with(strtolower($path), '.zip')) {
                $tempDir = storage_path('app/tmp/message-imports/'.uniqid());
                $path = $this->extractArchive($path, $tempDir);
            }

            $importSource = $this->locateImportSource($path);

            return $importSource['type'] === 'whatsapp'
                ? $this->whatsappChatImportService->importFromPath($importSource['path'], $meName)
                : $this->jsonMessageImportService->importFromPath($importSource['path'], $meName);
        } finally {
            if ($tempDir !== null) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    protected function extractArchive(string $archivePath, string $destination): string
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

        return $destination;
    }

    protected function locateImportSource(string $path): array
    {
        if ($this->isValidWhatsappExport($path)) {
            return [
                'type' => 'whatsapp',
                'path' => $path,
            ];
        }

        if ($this->isValidMessagesDirectory($path)) {
            return [
                'type' => 'json',
                'path' => $path,
            ];
        }

        if (! is_dir($path)) {
            throw new \RuntimeException('The import path does not exist or is not a supported message export.');
        }

        $messagesDirectory = $this->locateMessagesDirectory($path);

        if ($messagesDirectory !== null) {
            return [
                'type' => 'json',
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

        throw new \RuntimeException('The provided export does not contain a supported Facebook, Instagram, or WhatsApp messages file.');
    }

    protected function locateMessagesDirectory(string $destination): ?string
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
}
