<?php

namespace App\Integrations\OpenAI;

use App\Contracts\ChatCompletionProviderInterface;
use App\Contracts\EmbeddingProviderInterface;
use App\Models\User;
use App\Models\UserAiCredential;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIService implements ChatCompletionProviderInterface, EmbeddingProviderInterface
{
    public const DEFAULT_EMBEDDING_MODEL = 'text-embedding-3-small';

    private string $apiKey;

    private string $model;

    public function __construct(string $apiKey, string $model = 'gpt-4o')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public static function forEmbeddings(?User $user = null): self
    {
        $credential = self::resolveCredential($user);

        if (! $credential) {
            throw new RuntimeException('No valid OpenAI credential configured. Save an OpenAI credential in AI settings.');
        }

        return new self(
            $credential->getActiveToken(),
            $credential->getMetadataValue('embedding_model', self::DEFAULT_EMBEDDING_MODEL)
        );
    }

    public static function forUser(User $user): self
    {
        $credential = self::resolveCredential($user);

        if (! $credential) {
            throw new RuntimeException('No valid OpenAI credential configured.');
        }

        return new self(
            $credential->getActiveToken(),
            $credential->getMetadataValue('chat_model', 'gpt-4o')
        );
    }

    protected function client(?int $timeout = null): PendingRequest
    {
        return Http::baseUrl('https://api.openai.com/v1')
            ->withToken($this->apiKey)
            ->connectTimeout(config('services.openai.connect_timeout', 10))
            ->timeout($timeout ?? config('services.openai.timeout', 60))
            ->retry(2, 1000, function (\Throwable $exception) {
                return $exception instanceof ConnectionException;
            })
            ->acceptJson();
    }

    public function embeddings(array $texts, ?string $model = null): array
    {
        $response = $this->client(config('services.openai.embedding_timeout', 180))->post('/embeddings', [
            'model' => $model ?? $this->model,
            'input' => $texts,
        ]);

        $data = $response->json();

        return array_map(fn ($item) => $item['embedding'], $data['data']);
    }

    public function chatCompletion(array $messages, ?string $model = null): string
    {
        $response = $this->client()->post('/chat/completions', [
            'model' => $model ?? $this->model,
            'messages' => $messages,
        ]);

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }

    public function chatCompletionWithMetadata(array $messages, ?string $model = null): array
    {
        $resolvedModel = $model ?? $this->model;

        $response = $this->client()->post('/chat/completions', [
            'model' => $resolvedModel,
            'messages' => $messages,
        ]);

        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => $data['usage'] ?? null,
            'model' => $data['model'] ?? $resolvedModel,
        ];
    }

    public function modelName(): string
    {
        return $this->model;
    }

    public static function refreshOAuthToken(UserAiCredential $credential): UserAiCredential
    {
        $response = Http::asForm()->post('https://auth.openai.com/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credential->refresh_token,
            'client_id' => config('services.openai.client_id'),
            'client_secret' => config('services.openai.client_secret'),
        ]);

        if (! $response->successful()) {
            $credential->update([
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
            ]);

            throw new RuntimeException('Failed to refresh OpenAI OAuth token. Please reconnect.');
        }

        $data = $response->json();

        $credential->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $credential->refresh_token,
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $credential->fresh();
    }

    protected static function resolveCredential(?User $user = null): ?UserAiCredential
    {
        if ($user) {
            return self::prepareCredential($user->aiCredentialFor('openai'));
        }

        foreach (UserAiCredential::query()->where('provider', 'openai')->latest('id')->get() as $credential) {
            $preparedCredential = self::prepareCredential($credential);

            if ($preparedCredential) {
                return $preparedCredential;
            }
        }

        return null;
    }

    protected static function prepareCredential(?UserAiCredential $credential): ?UserAiCredential
    {
        if (! $credential) {
            return null;
        }

        if ($credential->isOAuth() && $credential->isTokenExpired()) {
            $credential = self::refreshOAuthToken($credential);
        }

        if (! $credential->hasValidCredential()) {
            return null;
        }

        return $credential;
    }
}
