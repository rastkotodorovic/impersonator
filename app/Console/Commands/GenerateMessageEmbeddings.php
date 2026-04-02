<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\MeilisearchService;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class GenerateMessageEmbeddings extends Command
{
    protected $signature = 'embeddings:generate
                            {--fresh : Drop and recreate the Meilisearch index}
                            {--batch-size=100 : Messages per OpenAI embedding request}';

    protected $description = 'Generate vector embeddings for imported messages and index into Meilisearch';

    protected MeilisearchService $meilisearch;

    protected OpenAIService $openai;

    protected int $indexed = 0;

    public function handle(MeilisearchService $meilisearch): int
    {
        $this->meilisearch = $meilisearch;
        $this->openai = OpenAIService::forEmbeddings();

        $this->setupIndex();

        $batchSize = (int) $this->option('batch-size');

        $this->info('Loading messages from 1-on-1 conversations...');

        $totalMessages = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.is_group_chat', false)
            ->count();

        $this->info("Found {$totalMessages} messages to embed.");

        $conversationIds = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.is_group_chat', false)
            ->distinct()
            ->pluck('messages.conversation_id');

        $bar = $this->output->createProgressBar($totalMessages);
        $bar->start();

        $pendingChunks = [];
        $pendingMessages = [];

        foreach ($conversationIds as $conversationId) {
            $messages = Message::where('conversation_id', $conversationId)
                ->orderBy('sent_at')
                ->get();

            foreach ($messages as $index => $message) {
                $chunk = $this->buildChunk($messages, $index);
                $pendingChunks[] = $chunk;
                $pendingMessages[] = $message;

                if (count($pendingChunks) >= $batchSize) {
                    $this->processBatch($pendingChunks, $pendingMessages);
                    $bar->advance(count($pendingChunks));
                    $pendingChunks = [];
                    $pendingMessages = [];
                }
            }
        }

        if (! empty($pendingChunks)) {
            $this->processBatch($pendingChunks, $pendingMessages);
            $bar->advance(count($pendingChunks));
        }

        $bar->finish();

        $this->newLine(2);
        $this->info('Embedding generation complete.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Messages indexed', $this->indexed],
                ['Batch size', $batchSize],
            ]
        );

        return self::SUCCESS;
    }

    protected function setupIndex(): void
    {
        $this->info('Configuring Meilisearch...');

        $this->meilisearch->enableVectorStore();

        if ($this->option('fresh')) {
            $this->warn('Dropping existing index...');
            $task = $this->meilisearch->deleteIndex('messages');
            if (isset($task['taskUid'])) {
                $this->meilisearch->waitForTask($task['taskUid']);
            }
        }

        $task = $this->meilisearch->createIndex('messages', 'id');
        if (isset($task['taskUid'])) {
            $this->meilisearch->waitForTask($task['taskUid']);
        }

        $task = $this->meilisearch->updateSettings('messages', [
            'searchableAttributes' => ['content'],
            'filterableAttributes' => ['conversation_id', 'is_group_chat'],
            'sortableAttributes' => ['sent_at_ts'],
            'embedders' => [
                'openai' => [
                    'source' => 'userProvided',
                    'dimensions' => 1536,
                ],
            ],
        ]);

        if (isset($task['taskUid'])) {
            $this->meilisearch->waitForTask($task['taskUid'], 120);
        }

        $this->info('Meilisearch index configured.');
    }

    protected function buildChunk(\Illuminate\Database\Eloquent\Collection $messages, int $index): string
    {
        $lines = [];
        $start = max(0, $index - 2);
        $end = min($messages->count() - 1, $index + 2);

        for ($i = $start; $i <= $end; $i++) {
            $msg = $messages[$i];
            $prefix = ($i === $index) ? '>>> ' : '';
            $suffix = ($i === $index) ? ' <<<' : '';
            $lines[] = "[{$msg->sender_name}]: {$prefix}{$msg->content}{$suffix}";
        }

        return implode("\n", $lines);
    }

    protected function processBatch(array $chunks, array $messages): void
    {
        $embeddings = $this->openai->embeddings($chunks);

        $documents = [];
        foreach ($messages as $i => $message) {
            $documents[] = [
                'id' => $message->id,
                'content' => $chunks[$i],
                'conversation_id' => $message->conversation_id,
                'sender_name' => $message->sender_name,
                'is_group_chat' => false,
                'sent_at_ts' => $message->timestamp_ms,
                '_vectors' => [
                    'openai' => $embeddings[$i],
                ],
            ];
        }

        $this->meilisearch->addDocuments('messages', $documents);
        $this->indexed += count($documents);
    }
}
