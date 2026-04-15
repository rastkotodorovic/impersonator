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
                $credential->getMetadataValue('embedding_model', 'voyage-3-lite'),
            );
        }

        throw new RuntimeException('No valid Voyage credential configured. Save a Voyage credential in AI settings.');
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
