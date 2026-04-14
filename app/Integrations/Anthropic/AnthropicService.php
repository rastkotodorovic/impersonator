<?php

namespace App\Integrations\Anthropic;

use App\Contracts\ChatCompletionProviderInterface;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicService implements ChatCompletionProviderInterface
{
    private string $apiKey;

    private string $model;

    public function __construct(string $apiKey, string $model = 'claude-3-7-sonnet-latest')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public static function forUser(User $user): self
    {
        $credential = $user->aiCredentialFor('anthropic');

        if ($credential?->hasValidCredential()) {
            return new self(
                $credential->getActiveToken(),
                config('services.anthropic.default_model', 'claude-3-7-sonnet-latest'),
            );
        }

        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey) {
            throw new RuntimeException('No valid Anthropic credential configured.');
        }

        return new self($apiKey, config('services.anthropic.default_model', 'claude-3-7-sonnet-latest'));
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl('https://api.anthropic.com/v1')
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(60)
            ->acceptJson();
    }

    public function chatCompletionWithMetadata(array $messages, ?string $model = null): array
    {
        $resolvedModel = $model ?? $this->model;
        $system = collect($messages)
            ->where('role', 'system')
            ->pluck('content')
            ->implode("\n\n");

        $anthropicMessages = collect($messages)
            ->reject(fn (array $message) => $message['role'] === 'system')
            ->map(function (array $message) {
                return [
                    'role' => $message['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $message['content'],
                ];
            })
            ->values()
            ->all();

        $response = $this->client()->post('/messages', [
            'model' => $resolvedModel,
            'max_tokens' => 512,
            'system' => $system === '' ? null : $system,
            'messages' => $anthropicMessages,
        ]);

        $data = $response->json();
        $content = collect($data['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        return [
            'content' => $content,
            'usage' => $data['usage'] ?? null,
            'model' => $data['model'] ?? $resolvedModel,
        ];
    }

    public function modelName(): string
    {
        return $this->model;
    }
}
