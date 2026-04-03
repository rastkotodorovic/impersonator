<?php

namespace App\Services;

use App\Contracts\MessageChannelInterface;
use App\Services\Channels\TelegramChannel;
use App\Services\Channels\WhatsappChannel;
use InvalidArgumentException;

class ChannelManager
{
    public function __construct(
        protected WhatsappChannel $whatsappChannel,
        protected TelegramChannel $telegramChannel,
    ) {}

    public function for(string $channel): MessageChannelInterface
    {
        return match ($channel) {
            'whatsapp' => $this->whatsappChannel,
            'telegram' => $this->telegramChannel,
            default => throw new InvalidArgumentException("Unsupported channel [{$channel}]."),
        };
    }
}
