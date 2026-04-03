<?php

namespace App\Contracts;

interface MessageChannelInterface
{
    public function key(): string;

    public function label(): string;

    public function sendMessage(string $connectionKey, string $recipient, string $message): void;
}
