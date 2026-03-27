<?php

namespace App\Services;

use App\Models\User;
use App\Models\WhatsappMessageLog;

class AutoReplyService
{
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
        WhatsappMessageLog::create([
            'user_id' => $user->id,
            'contact_phone' => $senderPhone,
            'direction' => 'incoming',
            'body' => $incomingMessage,
            'waha_message_id' => $wahaMessageId,
        ]);

        // RAG retrieval
        $context = $this->retrieval->retrieveContext($incomingMessage);

        // Build LLM prompt
        $messages = $this->buildPrompt($user->name, $senderPhone, $incomingMessage, $context);

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

    protected function buildPrompt(string $userName, string $senderPhone, string $incomingMessage, array $context): array
    {
        $systemPrompt = <<<PROMPT
You are impersonating {$userName} in a WhatsApp conversation. Reply exactly as {$userName} would — match their tone, vocabulary, message length, and language.

Rules:
- Match the language the sender uses (Serbian Cyrillic, Serbian Latin, or English)
- Keep replies natural and conversational, matching {$userName}'s typical message length
- If unsure how {$userName} would respond, be brief and non-committal
- Never reveal you are an AI or that this is automated
- Reply with plain text only, no quotes or formatting
PROMPT;

        if ($context['snippets']) {
            $systemPrompt .= "\n\nHere are examples of how {$userName} writes, from past conversations. Study the style, tone, and vocabulary:\n\n" . $context['snippets'];
        }

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "New WhatsApp message from {$senderPhone}:\n\n{$incomingMessage}"],
        ];
    }
}
