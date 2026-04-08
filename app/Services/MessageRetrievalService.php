<?php

namespace App\Services;

use App\Integrations\Meilisearch\MeilisearchService;
use App\Integrations\OpenAI\OpenAIService;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;

class MessageRetrievalService
{
    protected int $maxContextChars = 12000; // ~3000 tokens

    public function __construct(
        protected MeilisearchService $meilisearch,
    ) {}

    public function retrieveContext(string $incomingMessage, ?User $user = null, int $matchLimit = 15): array
    {
        $search = $this->searchMessages($incomingMessage, $user, $matchLimit);
        $matches = $search['matches'];

        if ($matches->isEmpty()) {
            return [
                'snippets' => '',
                'count' => 0,
                'hits' => [],
                'snippet_blocks' => [],
            ];
        }

        $snippets = $this->buildContextSnippets($matches, $search['raw_hits']);

        return [
            'snippets' => $snippets['text'],
            'count' => $matches->count(),
            'hits' => $this->buildTraceHits($matches, $search['raw_hits']),
            'snippet_blocks' => $snippets['blocks'],
        ];
    }

    protected function searchMessages(string $query, ?User $user, int $limit): array
    {
        $openai = OpenAIService::forEmbeddings($user);
        $embeddings = $openai->embeddings([$query]);
        $vector = $embeddings[0];

        $results = $this->meilisearch->search('messages', [
            'q' => $query,
            'vector' => $vector,
            'showRankingScore' => true,
            'hybrid' => [
                'semanticRatio' => 0.7,
                'embedder' => 'openai',
            ],
            'limit' => $limit,
        ]);

        $messageIds = array_column($results['hits'] ?? [], 'id');

        if (empty($messageIds)) {
            return [
                'matches' => collect(),
                'raw_hits' => [],
            ];
        }

        return [
            'matches' => Message::select('messages.*', 'conversations.title as conversation_title')
                ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
                ->whereIn('messages.id', $messageIds)
                ->get()
                ->sortBy(fn (Message $message) => array_search($message->id, $messageIds, true))
                ->values(),
            'raw_hits' => $results['hits'] ?? [],
        ];
    }

    protected function buildContextSnippets(Collection $matches, array $rawHits): array
    {
        $snippets = [];
        $blocks = [];
        $totalChars = 0;
        $rawHitsById = collect($rawHits)->keyBy('id');

        $grouped = $matches->groupBy('conversation_id');

        foreach ($grouped as $conversationId => $conversationMatches) {
            $title = $conversationMatches->first()->conversation_title;

            foreach ($conversationMatches as $match) {
                if ($totalChars >= $this->maxContextChars) {
                    break 2;
                }

                $window = $this->getSurroundingMessages($match->conversation_id, $match->sent_at, 3);

                $snippet = "Conversation with {$title}:\n";
                $windowMessages = [];
                foreach ($window as $msg) {
                    $line = "[{$msg->sender_name}]: {$msg->content}";
                    $snippet .= $line."\n";
                    $windowMessages[] = [
                        'id' => $msg->id,
                        'sender_name' => $msg->sender_name,
                        'content' => $msg->content,
                        'sent_at' => $msg->sent_at,
                    ];
                }

                $totalChars += strlen($snippet);
                $snippets[] = $snippet;
                $rawHit = $rawHitsById->get($match->id, []);
                $blocks[] = [
                    'conversation_id' => $conversationId,
                    'conversation_title' => $title,
                    'matched_message' => [
                        'id' => $match->id,
                        'sender_name' => $match->sender_name,
                        'content' => $match->content,
                        'sent_at' => $match->sent_at,
                        'ranking_score' => $rawHit['_rankingScore'] ?? null,
                    ],
                    'messages' => $windowMessages,
                ];
            }
        }

        return [
            'text' => implode("\n---\n\n", $snippets),
            'blocks' => $blocks,
        ];
    }

    protected function buildTraceHits(Collection $matches, array $rawHits): array
    {
        $rawHitsById = collect($rawHits)->keyBy('id');

        return $matches->values()->map(function (Message $match, int $index) use ($rawHitsById) {
            $rawHit = $rawHitsById->get($match->id, []);

            return [
                'rank' => $index + 1,
                'message_id' => $match->id,
                'conversation_id' => $match->conversation_id,
                'conversation_title' => $match->conversation_title,
                'sender_name' => $match->sender_name,
                'content' => $match->content,
                'sent_at' => $match->sent_at,
                'ranking_score' => $rawHit['_rankingScore'] ?? null,
            ];
        })->all();
    }

    protected function getSurroundingMessages(int $conversationId, string $sentAt, int $radius): Collection
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
