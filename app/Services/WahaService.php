<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WahaService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.waha.api_url'), '/');
        $this->apiKey = config('services.waha.api_key');
    }

    protected function client(): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout(15)
            ->acceptJson();

        if ($this->apiKey) {
            $client->withHeader('X-Api-Key', $this->apiKey);
        }

        return $client;
    }

    public function startSession(string $sessionName, ?string $webhookUrl = null): array
    {
        $payload = [
            'name' => $sessionName,
            'config' => [
                'proxy' => null,
                'webhooks' => [],
            ],
        ];

        if ($webhookUrl) {
            $payload['config']['webhooks'][] = [
                'url' => $webhookUrl,
                'events' => ['session.status'],
            ];
        }

        $response = $this->client()->post('/api/sessions', $payload);

        return $response->json();
    }

    public function getQrCode(string $sessionName): ?array
    {
        $response = $this->client()->get("/api/{$sessionName}/auth/qr", [
            'format' => 'image',
        ]);

        if ($response->successful()) {
            return [
                'image' => base64_encode($response->body()),
                'mimetype' => $response->header('Content-Type'),
            ];
        }

        return null;
    }

    public function getSessionStatus(string $sessionName): ?string
    {
        $response = $this->client()->get("/api/sessions/{$sessionName}");

        if ($response->successful()) {
            return $response->json('status');
        }

        return null;
    }

    public function getSession(string $sessionName): ?array
    {
        $response = $this->client()->get("/api/sessions/{$sessionName}");

        return $response->successful() ? $response->json() : null;
    }

    public function stopSession(string $sessionName): bool
    {
        $response = $this->client()->post("/api/sessions/{$sessionName}/stop");

        return $response->successful();
    }

    public function logoutSession(string $sessionName): bool
    {
        $response = $this->client()->post("/api/sessions/{$sessionName}/logout");

        return $response->successful();
    }

    public function isHealthy(): bool
    {
        try {
            $response = $this->client()->get('/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
