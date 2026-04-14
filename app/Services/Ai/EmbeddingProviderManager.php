<?php

namespace App\Services\Ai;

use App\Contracts\EmbeddingProviderInterface;
use App\Integrations\OpenAI\OpenAIService;
use App\Integrations\Voyage\VoyageService;
use App\Models\User;

class EmbeddingProviderManager
{
    public function forUser(?User $user = null): EmbeddingProviderInterface
    {
        return match ($user?->embedding_provider ?? config('services.ai.default_embedding_provider', 'openai')) {
            'voyage' => VoyageService::forEmbeddings($user),
            default => OpenAIService::forEmbeddings($user),
        };
    }
}
