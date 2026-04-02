<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MeilisearchService
{
    protected string $host;

    protected string $key;

    public function __construct()
    {
        $this->host = rtrim(config('services.meilisearch.host'), '/');
        $this->key = config('services.meilisearch.key', '');
    }

    protected function client(): PendingRequest
    {
        $client = Http::baseUrl($this->host)
            ->timeout(30)
            ->acceptJson();

        if ($this->key) {
            $client->withToken($this->key);
        }

        return $client;
    }

    public function enableVectorStore(): array
    {
        $response = $this->client()->patch('/experimental-features', [
            'vectorStore' => true,
        ]);

        return $response->json() ?? [];
    }

    public function createIndex(string $indexName, string $primaryKey = 'id'): array
    {
        $response = $this->client()->post('/indexes', [
            'uid' => $indexName,
            'primaryKey' => $primaryKey,
        ]);

        return $response->json() ?? [];
    }

    public function deleteIndex(string $indexName): array
    {
        $response = $this->client()->delete("/indexes/{$indexName}");

        return $response->json() ?? [];
    }

    public function updateSettings(string $indexName, array $settings): array
    {
        $response = $this->client()->patch("/indexes/{$indexName}/settings", $settings);

        return $response->json() ?? [];
    }

    public function addDocuments(string $indexName, array $documents): array
    {
        $response = $this->client()->post("/indexes/{$indexName}/documents", $documents);

        return $response->json() ?? [];
    }

    public function search(string $indexName, array $params): array
    {
        $response = $this->client()->post("/indexes/{$indexName}/search", $params);

        return $response->json() ?? [];
    }

    public function getTask(int $taskUid): array
    {
        $response = $this->client()->get("/tasks/{$taskUid}");

        return $response->json() ?? [];
    }

    public function waitForTask(int $taskUid, int $timeoutSeconds = 60): array
    {
        $start = time();

        while (time() - $start < $timeoutSeconds) {
            $task = $this->getTask($taskUid);
            $status = $task['status'] ?? 'unknown';

            if (in_array($status, ['succeeded', 'failed'])) {
                return $task;
            }

            usleep(500000); // 500ms
        }

        return ['status' => 'timeout'];
    }
}
