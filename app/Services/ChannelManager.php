<?php

namespace App\Services;

use App\Contracts\MessageChannelInterface;
use App\Services\Channels\WhatsappChannel;
use InvalidArgumentException;

class ChannelManager
{
    public function __construct(
        protected WhatsappChannel $whatsappChannel,
    ) {}

    public function for(string $channel): MessageChannelInterface
    {
        return match ($channel) {
            'whatsapp' => $this->whatsappChannel,
            default => throw new InvalidArgumentException("Unsupported channel [{$channel}]."),
        };
    }
}
