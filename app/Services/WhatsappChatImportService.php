<?php

namespace App\Services;

use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsappChatImportService
{
    protected const TIMESTAMP_FORMATS = [
        'j. n. Y., g:i:s A',
        'j. n. Y., g:i A',
        'j. n. y., g:i:s A',
        'j. n. y., g:i A',
        'j/m/Y, H:i:s',
        'j/m/Y, H:i',
        'j/m/y, H:i:s',
        'j/m/y, H:i',
        'n/j/Y, g:i:s A',
        'n/j/Y, g:i A',
        'n/j/y, g:i:s A',
        'n/j/y, g:i A',
        'm/d/Y, g:i:s A',
        'm/d/Y, g:i A',
        'm/d/y, g:i:s A',
        'm/d/y, g:i A',
    ];

    public function importFromPath(string $path, string $meName): array
    {
        if (! is_file($path)) {
            throw new \RuntimeException("WhatsApp export not found: {$path}");
        }

        [$messages, $skipped] = $this->parseMessages($path);

        if ($messages === []) {
            return [
                'threads_found' => 1,
                'conversations' => Conversation::count(),
                'messages_imported' => 0,
                'messages_skipped' => $skipped,
            ];
        }

        $participants = array_values(array_unique(array_map(
            fn (array $message) => $message['sender_name'],
            $messages
        )));

        $title = $this->resolveConversationTitle($participants, $meName, $path);
        $threadPath = $this->buildThreadPath($title, $participants);

        $imported = DB::transaction(function () use ($threadPath, $title, $participants, $messages, $meName) {
            $conversation = Conversation::updateOrCreate(
                ['thread_path' => $threadPath],
                [
                    'title' => $title,
                    'source' => 'whatsapp',
                    'participants' => $participants,
                    'participant_count' => count($participants),
                    'is_group_chat' => count($participants) > 2,
                ]
            );

            $batch = [];
            $imported = 0;

            foreach ($messages as $message) {
                $batch[] = [
                    'conversation_id' => $conversation->id,
                    'sender_name' => $message['sender_name'],
                    'is_from_me' => $this->namesMatch($message['sender_name'], $meName),
                    'content' => $message['content'],
                    'timestamp_ms' => $message['timestamp_ms'],
                    'sent_at' => $message['sent_at'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= 500) {
                    $imported += $this->upsertBatch($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $imported += $this->upsertBatch($batch);
            }

            return $imported;
        });

        return [
            'threads_found' => 1,
            'conversations' => Conversation::count(),
            'messages_imported' => $imported,
            'messages_skipped' => $skipped,
        ];
    }

    protected function parseMessages(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            throw new \RuntimeException("Unable to read WhatsApp export: {$path}");
        }

        $messages = [];
        $skipped = 0;
        $currentMessage = null;

        foreach ($lines as $line) {
            $parsed = $this->parseLine($line);

            if ($parsed !== null) {
                if ($currentMessage !== null) {
                    if ($this->shouldSkipMessage($currentMessage['content'])) {
                        $skipped++;
                    } else {
                        $messages[] = $currentMessage;
                    }
                }

                if ($parsed['type'] === 'system') {
                    $currentMessage = null;
                    $skipped++;

                    continue;
                }

                $currentMessage = $parsed;

                continue;
            }

            if ($currentMessage === null) {
                continue;
            }

            if ($line === '') {
                $currentMessage['content'] .= "\n";

                continue;
            }

            $separator = $currentMessage['content'] === '' || str_ends_with($currentMessage['content'], "\n")
                ? ''
                : "\n";

            $currentMessage['content'] .= $separator.$this->cleanText($line);
        }

        if ($currentMessage !== null) {
            if ($this->shouldSkipMessage($currentMessage['content'])) {
                $skipped++;
            } else {
                $messages[] = $currentMessage;
            }
        }

        return [$messages, $skipped];
    }

    protected function parseLine(string $line): ?array
    {
        $line = ltrim($line, "\xEF\xBB\xBF");

        if (str_starts_with($line, '[')) {
            $closingBracket = strpos($line, '] ');

            if ($closingBracket === false) {
                return null;
            }

            return $this->parsePayload(
                substr($line, 1, $closingBracket - 1),
                substr($line, $closingBracket + 2),
            );
        }

        $delimiter = strpos($line, ' - ');

        if ($delimiter === false) {
            return null;
        }

        return $this->parsePayload(
            substr($line, 0, $delimiter),
            substr($line, $delimiter + 3),
        );
    }

    protected function parsePayload(string $timestamp, string $payload): ?array
    {
        try {
            $sentAt = $this->parseTimestamp($timestamp);
        } catch (\Throwable) {
            return null;
        }

        $separator = strpos($payload, ': ');

        if ($separator === false) {
            return ['type' => 'system'];
        }

        $senderName = $this->cleanText(substr($payload, 0, $separator));

        if ($senderName === '') {
            return ['type' => 'system'];
        }

        return [
            'type' => 'message',
            'sender_name' => $senderName,
            'content' => $this->cleanText(substr($payload, $separator + 2)),
            'timestamp_ms' => $sentAt->valueOf(),
            'sent_at' => $sentAt,
        ];
    }

    protected function parseTimestamp(string $timestamp): Carbon
    {
        $timestamp = $this->normalizeTimestamp($timestamp);

        foreach (self::TIMESTAMP_FORMATS as $format) {
            try {
                return Carbon::createFromFormat($format, $timestamp, config('app.timezone'));
            } catch (\Throwable) {
            }
        }

        return Carbon::parse($timestamp, config('app.timezone'));
    }

    protected function normalizeTimestamp(string $timestamp): string
    {
        $timestamp = preg_replace('/[\x{00A0}\x{200E}\x{200F}\x{202A}\x{202C}\x{202F}\x{2060}]/u', ' ', $timestamp) ?? $timestamp;

        return trim(preg_replace('/\s+/u', ' ', $timestamp) ?? $timestamp);
    }

    protected function cleanText(string $text): string
    {
        $text = str_replace("\r", '', $text);
        $text = preg_replace('/[\x{200E}\x{200F}\x{202A}\x{202C}\x{2060}]/u', '', $text) ?? $text;

        return trim($text);
    }

    protected function shouldSkipMessage(string $content): bool
    {
        $normalized = Str::lower(preg_replace('/\s+/u', ' ', trim($content)) ?? $content);

        if ($normalized === '') {
            return true;
        }

        return preg_match('/^(messages and calls are end-to-end encrypted|<media omitted>|image omitted|video omitted|audio omitted|document omitted|gif omitted|sticker omitted)/u', $normalized) === 1;
    }

    protected function resolveConversationTitle(array $participants, string $meName, string $path): string
    {
        $others = array_values(array_filter(
            $participants,
            fn (string $participant) => ! $this->namesMatch($participant, $meName)
        ));

        if (count($participants) === 2 && $others !== []) {
            return $others[0];
        }

        $filename = pathinfo($path, PATHINFO_FILENAME);

        if ($filename !== '' && $filename !== '_chat') {
            return $filename;
        }

        if ($others !== []) {
            return implode(', ', array_slice($others, 0, 3));
        }

        return 'WhatsApp chat';
    }

    protected function buildThreadPath(string $title, array $participants): string
    {
        $normalizedParticipants = array_map(
            fn (string $participant) => Str::lower(trim($participant)),
            $participants
        );

        sort($normalizedParticipants);

        $titleSlug = Str::slug($title);

        if ($titleSlug === '') {
            $titleSlug = 'chat';
        }

        return 'whatsapp/'.$titleSlug.'-'.substr(sha1(implode('|', $normalizedParticipants)), 0, 16);
    }

    protected function namesMatch(string $left, string $right): bool
    {
        return Str::lower(trim($left)) === Str::lower(trim($right));
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
}
