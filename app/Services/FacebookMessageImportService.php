<?php

namespace App\Services;

use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FacebookMessageImportService
{
    protected const SOURCE_DIRECTORIES = ['inbox', 'e2ee_cutover', 'message_requests'];

    protected const ATTACHMENT_KEYS = ['photos', 'videos', 'audio_files', 'gifs', 'files', 'sticker', 'share'];

    public function importFromPath(string $basePath, string $meName): array
    {
        if (! is_dir($basePath)) {
            throw new \RuntimeException("Directory not found: {$basePath}");
        }

        $threads = $this->discoverThreads($basePath);
        $imported = 0;
        $skipped = 0;

        foreach ($threads as $thread) {
            $result = $this->importThread($thread, $meName);
            $imported += $result['imported'];
            $skipped += $result['skipped'];
        }

        return [
            'threads_found' => count($threads),
            'conversations' => Conversation::count(),
            'messages_imported' => $imported,
            'messages_skipped' => $skipped,
        ];
    }

    public function discoverThreads(string $basePath): array
    {
        $threads = [];

        foreach (self::SOURCE_DIRECTORIES as $source) {
            $sourceDir = $basePath.'/'.$source;

            if (! is_dir($sourceDir)) {
                continue;
            }

            foreach (glob($sourceDir.'/*', GLOB_ONLYDIR) as $threadDir) {
                $threads[] = [
                    'path' => $threadDir,
                    'source' => $source,
                ];
            }
        }

        return $threads;
    }

    protected function importThread(array $thread, string $meName): array
    {
        $threadDir = $thread['path'];
        $source = $thread['source'];
        $jsonFiles = glob($threadDir.'/message_*.json');

        if (empty($jsonFiles)) {
            return ['imported' => 0, 'skipped' => 0];
        }

        rsort($jsonFiles);

        $firstFileData = json_decode(file_get_contents(end($jsonFiles)), true);

        if (! $firstFileData) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $participants = array_map(
            fn (array $participant) => $this->decodeText($participant['name']),
            $firstFileData['participants'] ?? []
        );

        $threadPath = $firstFileData['thread_path'] ?? ($source.'/'.basename($threadDir));
        $title = $this->decodeText($firstFileData['title'] ?? basename($threadDir));

        return DB::transaction(function () use ($threadPath, $title, $source, $participants, $jsonFiles, $meName) {
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
            $imported = 0;
            $skipped = 0;

            foreach ($jsonFiles as $jsonFile) {
                $data = json_decode(file_get_contents($jsonFile), true);

                if (! $data || empty($data['messages'])) {
                    continue;
                }

                $messages = array_reverse($data['messages']);

                foreach ($messages as $message) {
                    if ($this->shouldSkipMessage($message)) {
                        $skipped++;

                        continue;
                    }

                    $senderName = $this->decodeText($message['sender_name']);
                    $content = $this->decodeText($message['content']);
                    $batch[] = [
                        'conversation_id' => $conversation->id,
                        'sender_name' => $senderName,
                        'is_from_me' => $senderName === $meName,
                        'content' => $content,
                        'timestamp_ms' => $message['timestamp_ms'],
                        'sent_at' => Carbon::createFromTimestampMs($message['timestamp_ms']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 500) {
                        $imported += $this->upsertBatch($batch);
                        $batch = [];
                    }
                }
            }

            if (! empty($batch)) {
                $imported += $this->upsertBatch($batch);
            }

            return [
                'imported' => $imported,
                'skipped' => $skipped,
            ];
        });
    }

    protected function upsertBatch(array $batch): int
    {
        DB::table('messages')->upsert(
            $batch,
            ['conversation_id', 'timestamp_ms', 'sender_name'],
            ['content', 'is_from_me', 'sent_at', 'updated_at']
        );

        return count($batch);
    }

    protected function shouldSkipMessage(array $message): bool
    {
        if (! isset($message['content']) || isset($message['call_duration'])) {
            return true;
        }

        $content = trim($this->decodeText((string) $message['content']));

        if ($content === '') {
            return true;
        }

        return $this->hasAttachmentPayload($message)
            && $this->isAttachmentPlaceholder($content);
    }

    protected function hasAttachmentPayload(array $message): bool
    {
        foreach (self::ATTACHMENT_KEYS as $key) {
            if (isset($message[$key])) {
                return true;
            }
        }

        return false;
    }

    protected function isAttachmentPlaceholder(string $content): bool
    {
        return preg_match('/^.+ sent an attachment\.$/i', $content) === 1;
    }

    protected function decodeText(string $text): string
    {
        return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    }
}
