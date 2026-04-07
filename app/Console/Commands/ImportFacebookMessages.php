<?php

namespace App\Console\Commands;

use App\Services\FacebookMessageImportService;
use Illuminate\Console\Command;

class ImportFacebookMessages extends Command
{
    protected $signature = 'import:facebook-messages
                            {--path=data/your_facebook_activity/messages : Path to Facebook messages directory}
                            {--me=Rastko Todorovic : Your name as it appears in the export}';

    protected $description = 'Import Facebook Messenger exports into the database';

    public function handle(FacebookMessageImportService $importer): int
    {
        $basePath = base_path($this->option('path'));
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
