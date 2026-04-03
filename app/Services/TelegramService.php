<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramService
{
    public function client(string $botToken): PendingRequest
    {
        return Http::baseUrl(rtrim(config('services.telegram.api_url', 'https://api.telegram.org'), '/')."/bot{$botToken}")
            ->timeout(30)
            ->acceptJson();
    }

    public function getMe(string $botToken): array
    {
        $data = $this->client($botToken)->get('/getMe')->json();

        if (! ($data['ok'] ?? false)) {
            throw new RuntimeException($data['description'] ?? 'Failed to validate Telegram bot.');
        }

        return $data['result'];
    }

    public function setWebhook(string $botToken, string $url): void
    {
        $data = $this->client($botToken)->post('/setWebhook', [
            'url' => $url,
            'drop_pending_updates' => false,
        ])->json();

        if (! ($data['ok'] ?? false)) {
            throw new RuntimeException($data['description'] ?? 'Failed to configure Telegram webhook.');
        }
    }

    public function deleteWebhook(string $botToken): void
    {
        $data = $this->client($botToken)->post('/deleteWebhook', [
            'drop_pending_updates' => false,
        ])->json();

        if (! ($data['ok'] ?? false)) {
            throw new RuntimeException($data['description'] ?? 'Failed to remove Telegram webhook.');
        }
    }

    public function sendMessage(string $botToken, string $chatId, string $message): void
    {
        $data = $this->client($botToken)->post('/sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
        ])->json();

        if (! ($data['ok'] ?? false)) {
            throw new RuntimeException($data['description'] ?? 'Failed to send Telegram message.');
        }
    }
}
