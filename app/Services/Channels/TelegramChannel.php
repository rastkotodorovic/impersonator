<?php

namespace App\Services\Channels;

use App\Contracts\MessageChannelInterface;
use App\Services\TelegramService;

class TelegramChannel implements MessageChannelInterface
{
    public function __construct(
        protected TelegramService $telegram,
    ) {}

    public function key(): string
    {
        return 'telegram';
    }

    public function label(): string
    {
        return 'Telegram';
    }

    public function sendMessage(string $connectionKey, string $recipient, string $message): void
    {
        $this->telegram->sendMessage($connectionKey, $recipient, $message);
    }
}
