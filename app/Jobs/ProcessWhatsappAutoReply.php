<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AutoReplyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWhatsappAutoReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(
        public int $userId,
        public string $senderPhone,
        public string $incomingMessage,
        public string $sessionName,
        public ?string $wahaMessageId = null,
    ) {}

    public function handle(AutoReplyService $autoReplyService): void
    {
        $user = User::findOrFail($this->userId);

        try {
            $autoReplyService->generateAndSendReply(
                $user,
                $this->senderPhone,
                $this->incomingMessage,
                $this->sessionName,
                $this->wahaMessageId,
            );
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}
