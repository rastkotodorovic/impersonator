<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class MessageEmbeddingService
{
    protected OpenAIService $openai;

    protected int $indexed = 0;

    public function __construct(
        protected MeilisearchService $meilisearch,
    ) {}

    public function generate(bool $fresh = false, int $batchSize = 100, ?User $user = null): array
    {
        $this->indexed = 0;
        $this->openai = OpenAIService::forEmbeddings($user);

        $this->setupIndex($fresh);

        $totalMessages = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.is_group_chat', false)
            ->count();

        $conversationIds = Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where('conversations.is_group_chat', false)
            ->distinct()
            ->pluck('messages.conversation_id');

        $pendingChunks = [];
        $pendingMessages = [];

        foreach ($conversationIds as $conversationId) {
            $messages = Message::where('conversation_id', $conversationId)
                ->orderBy('sent_at')
                ->get();

            foreach ($messages as $index => $message) {
                $pendingChunks[] = $this->buildChunk($messages, $index);
                $pendingMessages[] = $message;

                if (count($pendingChunks) >= $batchSize) {
                    $this->processBatch($pendingChunks, $pendingMessages);
                    $pendingChunks = [];
                    $pendingMessages = [];
                }
            }
        }

        if (! empty($pendingChunks)) {
            $this->processBatch($pendingChunks, $pendingMessages);
        }

        return [
            'messages_indexed' => $this->indexed,
            'total_messages' => $totalMessages,
            'batch_size' => $batchSize,
        ];
    }

    protected function setupIndex(bool $fresh): void
    {
        $this->meilisearch->enableVectorStore();

        if ($fresh) {
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
    }

    protected function buildChunk(EloquentCollection $messages, int $index): string
    {
        $lines = [];
        $start = max(0, $index - 2);
        $end = min($messages->count() - 1, $index + 2);

        for ($i = $start; $i <= $end; $i++) {
            $message = $messages[$i];
            $prefix = $i === $index ? '>>> ' : '';
            $suffix = $i === $index ? ' <<<' : '';
            $lines[] = "[{$message->sender_name}]: {$prefix}{$message->content}{$suffix}";
        }

        return implode("\n", $lines);
    }

    protected function processBatch(array $chunks, array $messages): void
    {
        $embeddings = $this->openai->embeddings($chunks);
        $documents = [];

        foreach ($messages as $index => $message) {
            $documents[] = [
                'id' => $message->id,
                'content' => $chunks[$index],
                'conversation_id' => $message->conversation_id,
                'sender_name' => $message->sender_name,
                'is_group_chat' => false,
                'sent_at_ts' => $message->timestamp_ms,
                '_vectors' => [
                    'openai' => $embeddings[$index],
                ],
            ];
        }

        $this->meilisearch->addDocuments('messages', $documents);
        $this->indexed += count($documents);
    }
}
