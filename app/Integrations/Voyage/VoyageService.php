<?php

namespace App\Integrations\Voyage;

use App\Contracts\EmbeddingProviderInterface;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VoyageService implements EmbeddingProviderInterface
{
    private string $apiKey;

    private string $model;

    public function __construct(string $apiKey, string $model = 'voyage-3-lite')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public static function forEmbeddings(?User $user = null): self
    {
        $credential = $user?->aiCredentialFor('voyage');

        if ($credential?->hasValidCredential()) {
            return new self(
                $credential->getActiveToken(),
                config('services.voyage.embedding_model', 'voyage-3-lite'),
            );
        }

        $apiKey = config('services.voyage.api_key');

        if (! $apiKey) {
            throw new RuntimeException('No valid Voyage credential configured. Save a Voyage API key in settings or set VOYAGE_API_KEY as a fallback.');
        }

        return new self($apiKey, config('services.voyage.embedding_model', 'voyage-3-lite'));
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl('https://api.voyageai.com/v1')
            ->withToken($this->apiKey)
            ->timeout(60)
            ->acceptJson();
    }

    public function embeddings(array $texts, ?string $model = null): array
    {
        $response = $this->client()->post('/embeddings', [
            'model' => $model ?? $this->model,
            'input' => $texts,
        ]);

        $data = $response->json();

        return array_map(fn ($item) => $item['embedding'], $data['data'] ?? []);
    }

    public function modelName(): string
    {
        return $this->model;
    }
}
