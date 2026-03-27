<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportFacebookMessages extends Command
{
    protected $signature = 'import:facebook-messages
                            {--path=data/your_facebook_activity/messages : Path to Facebook messages directory}
                            {--me=Rastko Todorovic : Your name as it appears in the export}';

    protected $description = 'Import Facebook Messenger exports into the database';

    protected int $imported = 0;

    protected int $skipped = 0;

    public function handle(): int
    {
        $basePath = base_path($this->option('path'));
        $meName = $this->option('me');

        if (! is_dir($basePath)) {
            $this->error("Directory not found: {$basePath}");

            return self::FAILURE;
        }

        $threads = $this->discoverThreads($basePath);

        if (empty($threads)) {
            $this->warn('No conversation threads found.');

            return self::SUCCESS;
        }

        $this->info("Found " . count($threads) . " conversation threads.");

        $this->withProgressBar($threads, function (array $thread) use ($meName) {
            $this->importThread($thread, $meName);
        });

        $this->newLine(2);
        $this->info("Import complete.");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Conversations', Conversation::count()],
                ['Messages imported', $this->imported],
                ['Messages skipped (non-text)', $this->skipped],
            ]
        );

        return self::SUCCESS;
    }

    protected function discoverThreads(string $basePath): array
    {
        $threads = [];

        foreach (['inbox', 'e2ee_cutover', 'message_requests'] as $source) {
            $sourceDir = $basePath . '/' . $source;

            if (! is_dir($sourceDir)) {
                continue;
            }

            foreach (glob($sourceDir . '/*', GLOB_ONLYDIR) as $threadDir) {
                $threads[] = [
                    'path' => $threadDir,
                    'source' => $source,
                ];
            }
        }

        return $threads;
    }

    protected function importThread(array $thread, string $meName): void
    {
        $threadDir = $thread['path'];
        $source = $thread['source'];

        $jsonFiles = glob($threadDir . '/message_*.json');

        if (empty($jsonFiles)) {
            return;
        }

        // Sort descending so message_3 is processed before message_2 before message_1
        // This gives us chronological order (oldest messages first)
        rsort($jsonFiles);

        // Read the last file (message_1.json, now last after rsort) for metadata
        // since all files share the same participants/title/thread_path
        $firstFileData = json_decode(file_get_contents(end($jsonFiles)), true);

        if (! $firstFileData) {
            return;
        }

        $participants = array_map(
            fn (array $p) => $this->decodeText($p['name']),
            $firstFileData['participants'] ?? []
        );

        $threadPath = $firstFileData['thread_path'] ?? ($source . '/' . basename($threadDir));
        $title = $this->decodeText($firstFileData['title'] ?? basename($threadDir));

        DB::transaction(function () use ($threadPath, $title, $source, $participants, $jsonFiles, $meName) {
            $conversation = Conversation::updateOrCreate(
                ['thread_path' => $threadPath],
                [
                    'title' => $title,
                    'source' => $source,
                    'participants' => $participants,
                    'participant_count' => count($participants),
                    'is_group_chat' => count($participants) > 2,
                ]
            );

            $batch = [];

            foreach ($jsonFiles as $jsonFile) {
                $data = json_decode(file_get_contents($jsonFile), true);

                if (! $data || empty($data['messages'])) {
                    continue;
                }

                // Messages are newest-first in each file, reverse for chronological order
                $messages = array_reverse($data['messages']);

                foreach ($messages as $msg) {
                    if (! isset($msg['content']) || isset($msg['call_duration'])) {
                        $this->skipped++;

                        continue;
                    }

                    $senderName = $this->decodeText($msg['sender_name']);

                    $batch[] = [
                        'conversation_id' => $conversation->id,
                        'sender_name' => $senderName,
                        'is_from_me' => $senderName === $meName,
                        'content' => $this->decodeText($msg['content']),
                        'timestamp_ms' => $msg['timestamp_ms'],
                        'sent_at' => Carbon::createFromTimestampMs($msg['timestamp_ms']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 500) {
                        $this->upsertBatch($batch);
                        $batch = [];
                    }
                }
            }

            if (! empty($batch)) {
                $this->upsertBatch($batch);
            }
        });
    }

    protected function upsertBatch(array $batch): void
    {
        DB::table('messages')->upsert(
            $batch,
            ['conversation_id', 'timestamp_ms', 'sender_name'],
            ['content', 'is_from_me', 'sent_at', 'updated_at']
        );

        $this->imported += count($batch);
    }

    protected function decodeText(string $text): string
    {
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }
}
