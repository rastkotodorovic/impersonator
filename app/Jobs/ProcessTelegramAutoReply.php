<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TelegramAutoReplyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessTelegramAutoReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public int $userId,
        public int $telegramBotId,
        public string $chatId,
        public string $contactIdentifier,
        public ?string $contactName,
        public string $incomingMessage,
        public ?string $telegramMessageId = null,
    ) {}

    public function handle(TelegramAutoReplyService $autoReplyService): void
    {
        $user = User::findOrFail($this->userId);

        $autoReplyService->generateAndSendReply(
            $user,
            $this->telegramBotId,
            $this->chatId,
            $this->contactIdentifier,
            $this->incomingMessage,
            $this->contactName,
            $this->telegramMessageId,
        );
    }
}
