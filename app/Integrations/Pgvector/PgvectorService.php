<?php

namespace App\Integrations\Pgvector;

use Illuminate\Support\Facades\DB;
use stdClass;

class PgvectorService
{
    public function clearMessageEmbeddings(): void
    {
        DB::table('message_embeddings')->truncate();
    }

    public function upsertMessageEmbeddings(array $documents): void
    {
        if ($documents === []) {
            return;
        }

        $timestamp = now();
        $rows = [];
        $bindings = [];

        foreach ($documents as $document) {
            $rows[] = '(?, ?, ?::vector, ?, ?)';
            $bindings[] = $document['message_id'];
            $bindings[] = $document['content'];
            $bindings[] = $this->toVectorLiteral($document['embedding']);
            $bindings[] = $timestamp;
            $bindings[] = $timestamp;
        }

        DB::insert(
            'INSERT INTO message_embeddings (message_id, content, embedding, created_at, updated_at) VALUES '
            .implode(', ', $rows)
            .' ON CONFLICT (message_id) DO UPDATE SET '
            .'content = EXCLUDED.content, '
            .'embedding = EXCLUDED.embedding, '
            .'updated_at = EXCLUDED.updated_at',
            $bindings,
        );
    }

    public function hybridSearchMessages(
        array $embedding,
        string $query,
        int $limit,
        ?int $conversationId = null,
    ): array
    {
        $candidateLimit = max($limit * 4, 40);
        $queryVector = $this->toVectorLiteral($embedding);
        $conversationFilterSql = $conversationId !== null ? ' AND m.conversation_id = ?' : '';
        $conversationBindings = $conversationId !== null ? [$conversationId] : [];

        if ($this->normalizeSearchQuery($query) === '') {
            return array_map(
                fn (stdClass $row) => (array) $row,
                DB::select(
                    <<<SQL
                        SELECT
                            me.message_id AS id,
                            1 - (me.embedding <=> ?::vector) AS vector_score,
                            0::double precision AS text_score,
                            1 - (me.embedding <=> ?::vector) AS ranking_score
                        FROM message_embeddings me
                        JOIN messages m ON m.id = me.message_id
                        JOIN conversations c ON c.id = m.conversation_id
                        WHERE c.is_group_chat = false
                        {$conversationFilterSql}
                        ORDER BY me.embedding <=> ?::vector, m.sent_at DESC
                        LIMIT ?
                    SQL,
                    [...[$queryVector, $queryVector], ...$conversationBindings, ...[$queryVector, $limit]],
                ),
            );
        }

        return array_map(
            fn (stdClass $row) => (array) $row,
            DB::select(
                <<<SQL
                    WITH input AS (
                        SELECT
                            ?::vector AS query_embedding,
                            websearch_to_tsquery('simple', ?) AS query_ts
                    ),
                    vector_candidates AS (
                        SELECT
                            me.message_id,
                            m.sent_at,
                            1 - (me.embedding <=> input.query_embedding) AS vector_score,
                            ts_rank_cd(me.content_search, input.query_ts) AS text_score
                        FROM message_embeddings me
                        JOIN messages m ON m.id = me.message_id
                        JOIN conversations c ON c.id = m.conversation_id
                        CROSS JOIN input
                        WHERE c.is_group_chat = false
                            {$conversationFilterSql}
                        ORDER BY me.embedding <=> input.query_embedding, m.sent_at DESC
                        LIMIT ?
                    ),
                    text_candidates AS (
                        SELECT
                            me.message_id,
                            m.sent_at,
                            1 - (me.embedding <=> input.query_embedding) AS vector_score,
                            ts_rank_cd(me.content_search, input.query_ts) AS text_score
                        FROM message_embeddings me
                        JOIN messages m ON m.id = me.message_id
                        JOIN conversations c ON c.id = m.conversation_id
                        CROSS JOIN input
                        WHERE c.is_group_chat = false
                            {$conversationFilterSql}
                            AND me.content_search @@ input.query_ts
                        ORDER BY ts_rank_cd(me.content_search, input.query_ts) DESC, m.sent_at DESC
                        LIMIT ?
                    ),
                    ranked_candidates AS (
                        SELECT
                            message_id,
                            MAX(sent_at) AS sent_at,
                            MAX(vector_score) AS vector_score,
                            MAX(text_score) AS text_score,
                            (0.72 * MAX(vector_score)) + (0.28 * LEAST(MAX(text_score), 1.0)) AS ranking_score
                        FROM (
                            SELECT * FROM vector_candidates
                            UNION ALL
                            SELECT * FROM text_candidates
                        ) candidates
                        GROUP BY message_id
                    )
                    SELECT
                        message_id AS id,
                        vector_score,
                        text_score,
                        ranking_score
                    FROM ranked_candidates
                    ORDER BY ranking_score DESC, sent_at DESC
                    LIMIT ?
                SQL,
                [
                    $queryVector,
                    $query,
                    ...$conversationBindings,
                    $candidateLimit,
                    ...$conversationBindings,
                    $candidateLimit,
                    $limit,
                ],
            ),
        );
    }

    protected function toVectorLiteral(array $embedding): string
    {
        $values = array_map(function ($value) {
            $float = (float) $value;

            if (! is_finite($float)) {
                $float = 0.0;
            }

            return rtrim(rtrim(number_format($float, 12, '.', ''), '0'), '.');
        }, $embedding);

        return '['.implode(',', array_map(fn (string $value) => $value === '' ? '0' : $value, $values)).']';
    }

    protected function normalizeSearchQuery(string $query): string
    {
        return trim((string) preg_replace('/[^\pL\pN]+/u', ' ', $query));
    }
}
