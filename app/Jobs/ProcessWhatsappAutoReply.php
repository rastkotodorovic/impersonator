<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WhatsappMessageLog;
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
        } catch (\Exception $e) {
            WhatsappMessageLog::create([
                'user_id' => $this->userId,
                'contact_phone' => $this->senderPhone,
                'direction' => 'outgoing',
                'body' => '',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
