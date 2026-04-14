<?php

namespace App\Contracts;

interface EmbeddingProviderInterface
{
    public function embeddings(array $texts, ?string $model = null): array;

    public function modelName(): string;
}
