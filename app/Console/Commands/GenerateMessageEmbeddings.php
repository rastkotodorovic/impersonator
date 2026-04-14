<?php

namespace App\Console\Commands;

use App\Services\MessageEmbeddingService;
use Illuminate\Console\Command;

class GenerateMessageEmbeddings extends Command
{
    protected $signature = 'embeddings:generate
                            {--fresh : Regenerate stored message embeddings from scratch}
                            {--batch-size=100 : Messages per embedding provider request}';

    protected $description = 'Generate vector embeddings for imported messages and store them in Postgres via pgvector';

    public function handle(MessageEmbeddingService $embeddingService): int
    {
        $batchSize = (int) $this->option('batch-size');

        $result = $embeddingService->generate((bool) $this->option('fresh'), $batchSize);

        $this->info('Embedding generation complete.');
        $this->table(['Metric', 'Count'], [
            ['Messages indexed', $result['messages_indexed']],
            ['Messages discovered', $result['total_messages']],
            ['Batch size', $result['batch_size']],
        ]);

        return self::SUCCESS;
    }
}
