<?php

namespace App\Services;

use App\Models\Message;

class MessageRetrievalService
{
    protected int $maxContextChars = 12000; // ~3000 tokens

    public function __construct(
        protected MeilisearchService $meilisearch,
    ) {}

    public function retrieveContext(string $incomingMessage, int $matchLimit = 15): array
    {
        $matches = $this->searchMessages($incomingMessage, $matchLimit);

        if ($matches->isEmpty()) {
            return [
                'snippets' => '',
                'count' => 0,
            ];
        }

        $snippets = $this->buildContextSnippets($matches);

        return [
            'snippets' => $snippets,
            'count' => $matches->count(),
        ];
    }

    protected function searchMessages(string $query, int $limit): \Illuminate\Support\Collection
    {
        // Embed the incoming message
        $openai = OpenAIService::forEmbeddings();
        $embeddings = $openai->embeddings([$query]);
        $vector = $embeddings[0];

        // Hybrid search: keyword (BM25) + vector similarity
        $results = $this->meilisearch->search('messages', [
            'q' => $query,
            'vector' => $vector,
            'hybrid' => [
                'semanticRatio' => 0.7,
                'embedder' => 'openai',
            ],
            'limit' => $limit,
        ]);

        $messageIds = array_column($results['hits'] ?? [], 'id');

        if (empty($messageIds)) {
            return collect();
        }

        // Load full messages from MySQL, preserving Meilisearch ranking
        return Message::select('messages.*', 'conversations.title as conversation_title')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->whereIn('messages.id', $messageIds)
            ->orderByRaw('FIELD(messages.id, ' . implode(',', $messageIds) . ')')
            ->get();
    }

    protected function buildContextSnippets(\Illuminate\Support\Collection $matches): string
    {
        $snippets = [];
        $totalChars = 0;

        // Group matches by conversation and fetch surrounding context
        $grouped = $matches->groupBy('conversation_id');

        foreach ($grouped as $conversationId => $conversationMatches) {
            $title = $conversationMatches->first()->conversation_title;

            foreach ($conversationMatches as $match) {
                if ($totalChars >= $this->maxContextChars) {
                    break 2;
                }

                $window = $this->getSurroundingMessages($match->conversation_id, $match->sent_at, 3);

                $snippet = "Conversation with {$title}:\n";
                foreach ($window as $msg) {
                    $line = "[{$msg->sender_name}]: {$msg->content}";
                    $snippet .= $line . "\n";
                }

                $totalChars += strlen($snippet);
                $snippets[] = $snippet;
            }
        }

        return implode("\n---\n\n", $snippets);
    }

    protected function getSurroundingMessages(int $conversationId, string $sentAt, int $radius): \Illuminate\Support\Collection
    {
        $before = Message::where('conversation_id', $conversationId)
            ->where('sent_at', '<=', $sentAt)
            ->orderByDesc('sent_at')
            ->limit($radius)
            ->get()
            ->reverse();

        $after = Message::where('conversation_id', $conversationId)
            ->where('sent_at', '>', $sentAt)
            ->orderBy('sent_at')
            ->limit($radius)
            ->get();

        return $before->merge($after)->unique('id')->sortBy('sent_at')->values();
    }
}
