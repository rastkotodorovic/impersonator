<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserOpenaiCredential;
use Generator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIService
{
    private string $apiKey;

    private string $model;

    public function __construct(string $apiKey, string $model = 'gpt-4o')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public static function forUser(User $user): self
    {
        $credential = $user->openaiCredential;

        if (! $credential || ! $credential->hasValidCredential()) {
            throw new RuntimeException('No valid OpenAI credential configured.');
        }

        if ($credential->isOAuth() && $credential->isTokenExpired()) {
            $credential = self::refreshOAuthToken($credential);
        }

        return new self(
            $credential->getActiveToken(),
            config('services.openai.default_model', 'gpt-4o')
        );
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl('https://api.openai.com/v1')
            ->withToken($this->apiKey)
            ->timeout(60)
            ->acceptJson();
    }

    public function streamChatCompletion(array $messages, ?string $model = null): Generator
    {
        $response = $this->client()
            ->withOptions(['stream' => true])
            ->post('/chat/completions', [
                'model' => $model ?? $this->model,
                'messages' => $messages,
                'stream' => true,
            ]);

        $body = $response->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || $line === 'data: [DONE]') {
                    if ($line === 'data: [DONE]') {
                        return;
                    }

                    continue;
                }

                if (str_starts_with($line, 'data: ')) {
                    $json = substr($line, 6);
                    $data = json_decode($json, true);

                    if ($data && isset($data['choices'][0]['delta']['content'])) {
                        yield $data['choices'][0]['delta']['content'];
                    }
                }
            }
        }
    }

    public static function refreshOAuthToken(UserOpenaiCredential $credential): UserOpenaiCredential
    {
        $response = Http::asForm()->post('https://auth.openai.com/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credential->oauth_refresh_token,
            'client_id' => config('services.openai.client_id'),
            'client_secret' => config('services.openai.client_secret'),
        ]);

        if (! $response->successful()) {
            $credential->update([
                'oauth_access_token' => null,
                'oauth_refresh_token' => null,
                'oauth_token_expires_at' => null,
            ]);

            throw new RuntimeException('Failed to refresh OpenAI OAuth token. Please reconnect.');
        }

        $data = $response->json();

        $credential->update([
            'oauth_access_token' => $data['access_token'],
            'oauth_refresh_token' => $data['refresh_token'] ?? $credential->oauth_refresh_token,
            'oauth_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $credential->fresh();
    }
}
