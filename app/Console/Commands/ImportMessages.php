<?php

namespace App\Console\Commands;

use App\Services\MessageImportService;
use Illuminate\Console\Command;

class ImportMessages extends Command
{
    protected $signature = 'import:messages
                            {--path=data/your_facebook_activity/messages : Path to a Facebook, Instagram, or WhatsApp export}
                            {--me=Rastko Todorovic : Your name as it appears in the export}';

    protected $description = 'Import Facebook, Instagram, or WhatsApp message exports into the database';

    public function handle(MessageImportService $importer): int
    {
        $basePath = (string) $this->option('path');
        $basePath = str_starts_with($basePath, DIRECTORY_SEPARATOR) ? $basePath : base_path($basePath);
        $meName = $this->option('me');

        try {
            $result = $importer->importFromPath($basePath, $meName);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['threads_found'] === 0) {
            $this->warn('No conversation threads found.');

            return self::SUCCESS;
        }

        $this->info('Import complete.');
        $this->table(['Metric', 'Count'], [
            ['Threads found', $result['threads_found']],
            ['Conversations', $result['conversations']],
            ['Messages imported', $result['messages_imported']],
            ['Messages skipped (non-text)', $result['messages_skipped']],
        ]);

        return self::SUCCESS;
    }
}
