<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappMessageLog;

class AutoReplyService
{
    protected int $recentMessageLimit = 12;

    public function __construct(
        protected MessageRetrievalService $retrieval,
        protected WahaService $waha,
    ) {}

    public function generateAndSendReply(
        User $user,
        string $senderPhone,
        string $incomingMessage,
        string $sessionName,
        ?string $wahaMessageId = null,
    ): WhatsappMessageLog {
        // Log incoming message
        $incomingLog = WhatsappMessageLog::create([
            'user_id' => $user->id,
            'contact_phone' => $senderPhone,
            'direction' => 'incoming',
            'body' => $incomingMessage,
            'waha_message_id' => $wahaMessageId,
        ]);

        // RAG retrieval
        $context = $this->retrieval->retrieveContext($incomingMessage);

        // Recent WhatsApp conversation history with this contact
        $recentConversation = $this->loadRecentConversation(
            $user,
            $senderPhone,
            $incomingLog->id,
        );

        // Build LLM prompt
        $messages = $this->buildPrompt(
            $user->name,
            $senderPhone,
            $incomingMessage,
            $context,
            $recentConversation,
        );

        // Generate reply via OpenAI
        $openai = OpenAIService::forUser($user);
        $reply = $openai->chatCompletion($messages);

        // Send reply via WAHA
        $chatId = $senderPhone . '@s.whatsapp.net';
        $this->waha->sendMessage($sessionName, $chatId, $reply);

        // Log outgoing message
        return WhatsappMessageLog::create([
            'user_id' => $user->id,
            'contact_phone' => $senderPhone,
            'direction' => 'outgoing',
            'body' => $reply,
            'context_messages_used' => $context['count'],
        ]);
    }

    protected function buildPrompt(
        string $userName,
        string $senderPhone,
        string $incomingMessage,
        array $context,
        array $recentConversation = [],
    ): array
    {
        $systemPrompt = <<<PROMPT
You are impersonating {$userName} in a WhatsApp conversation. Reply exactly as {$userName} would — match their tone, vocabulary, message length, and language.

Rules:
- Use the recent WhatsApp conversation as the source of truth for what is being discussed right now
- Match the language the sender uses (Serbian Cyrillic, Serbian Latin, or English)
- Keep replies natural and conversational, matching {$userName}'s typical message length
- If unsure how {$userName} would respond, be brief and non-committal
- Never reveal you are an AI or that this is automated
- Reply with plain text only, no quotes or formatting
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        if (! empty($recentConversation)) {
            $messages = array_merge($messages, $recentConversation);
        }

        if ($context['snippets']) {
            $messages[] = [
                'role' => 'system',
                'content' => "Historical examples of how {$userName} writes. Use these for tone, vocabulary, and style only. Do not treat them as facts about the current chat.\n\n{$context['snippets']}",
            ];
        }

        $messages[] = ['role' => 'user', 'content' => "New WhatsApp message from {$senderPhone}:\n\n{$incomingMessage}"];

        return $messages;
    }

    protected function loadRecentConversation(User $user, string $senderPhone, ?int $excludeLogId = null): array
    {
        $logs = WhatsappMessageLog::query()
            ->where('user_id', $user->id)
            ->where('contact_phone', $senderPhone)
            ->whereNull('error')
            ->where('body', '!=', '');
        if ($excludeLogId) {
            $logs->whereKeyNot($excludeLogId);
        }

        return $logs
            ->orderByDesc('created_at')
            ->limit($this->recentMessageLimit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (WhatsappMessageLog $log) => [
                'role' => $log->direction === 'incoming' ? 'user' : 'assistant',
                'content' => $log->body,
            ])
            ->all();
    }
}
