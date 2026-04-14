<?php

namespace App\Services;

use App\Contracts\EmbeddingProviderInterface;
use App\Integrations\Pgvector\PgvectorService;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\EmbeddingProviderManager;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class MessageEmbeddingService
{
    protected EmbeddingProviderInterface $embeddingProvider;

    protected int $indexed = 0;

    public function __construct(
        protected PgvectorService $pgvector,
        protected EmbeddingProviderManager $embeddingProviders,
    ) {}

    public function generate(bool $fresh = false, int $batchSize = 100, ?User $user = null): array
    {
        $this->indexed = 0;
        $this->embeddingProvider = $this->embeddingProviders->forUser($user);

        $this->setupStorage($fresh);

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

    protected function setupStorage(bool $fresh): void
    {
        if ($fresh) {
            $this->pgvector->clearMessageEmbeddings();
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
        $embeddings = $this->embeddingProvider->embeddings($chunks);
        $documents = [];

        foreach ($messages as $index => $message) {
            $documents[] = [
                'message_id' => $message->id,
                'content' => $chunks[$index],
                'embedding' => $embeddings[$index],
            ];
        }

        $this->pgvector->upsertMessageEmbeddings($documents);
        $this->indexed += count($documents);
    }
}
