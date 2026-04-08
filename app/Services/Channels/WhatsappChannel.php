<?php

namespace App\Services\Channels;

use App\Contracts\MessageChannelInterface;
use App\Integrations\Waha\WahaService;

class WhatsappChannel implements MessageChannelInterface
{
    public function __construct(
        protected WahaService $waha,
    ) {}

    public function key(): string
    {
        return 'whatsapp';
    }

    public function label(): string
    {
        return 'WhatsApp';
    }

    public function sendMessage(string $connectionKey, string $recipient, string $message): void
    {
        $this->waha->sendMessage($connectionKey, $recipient, $message);
    }
}
