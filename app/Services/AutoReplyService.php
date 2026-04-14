<?php

namespace App\Services;

use App\Integrations\Waha\WahaService;
use App\Models\AiTrace;
use App\Models\AutoReplyContact;
use App\Models\User;
use App\Models\WhatsappMessageLog;
use App\Services\Ai\ChatProviderManager;

class AutoReplyService
{
    protected int $recentMessageLimit = 12;

    public function __construct(
        protected MessageRetrievalService $retrieval,
        protected ChatProviderManager $chatProviders,
        protected ChannelManager $channels,
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

        $trace = AiTrace::create([
            'user_id' => $user->id,
            'contact_phone' => $senderPhone,
            'incoming_whatsapp_message_log_id' => $incomingLog->id,
            'status' => 'processing',
            'input_message' => $incomingMessage,
            'retrieval_query' => $incomingMessage,
        ]);

        $chatId = $senderPhone.'@s.whatsapp.net';
        $typingStarted = $this->safelyStartTyping($sessionName, $chatId);

        try {
            $contact = $this->resolveContact($user, $senderPhone);
            $context = $this->retrieval->retrieveContext(
                $incomingMessage,
                $user,
                15,
                $contact?->preferred_conversation_id,
            );

            $recentConversation = $this->loadRecentConversation(
                $user,
                $senderPhone,
                $incomingLog->id,
            );

            $messages = $this->buildPrompt(
                $user->name,
                $senderPhone,
                $incomingMessage,
                $context,
                $recentConversation,
                $contact?->ai_additional_instructions,
            );

            $trace->update([
                'recent_conversation' => $recentConversation,
                'retrieval_hits' => $context['hits'],
                'context_snippets' => $context['snippet_blocks'],
                'final_prompt' => $messages,
            ]);

            $chatProvider = $this->chatProviders->forUser($user);
            $startedAt = microtime(true);
            $completion = $chatProvider->chatCompletionWithMetadata($messages);
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $reply = $completion['content'];

            $this->channels->for('whatsapp')->sendMessage($sessionName, $chatId, $reply);

            $outgoingLog = WhatsappMessageLog::create([
                'user_id' => $user->id,
                'contact_phone' => $senderPhone,
                'direction' => 'outgoing',
                'body' => $reply,
                'context_messages_used' => $context['count'],
            ]);

            $trace->update([
                'outgoing_whatsapp_message_log_id' => $outgoingLog->id,
                'status' => 'completed',
                'model' => $completion['model'] ?? $chatProvider->modelName(),
                'model_response' => $reply,
                'usage' => $completion['usage'],
                'latency_ms' => $latencyMs,
            ]);

            return $outgoingLog;
        } catch (\Throwable $exception) {
            $outgoingLog = WhatsappMessageLog::create([
                'user_id' => $user->id,
                'contact_phone' => $senderPhone,
                'direction' => 'outgoing',
                'body' => '',
                'error' => $exception->getMessage(),
            ]);

            $trace->update([
                'outgoing_whatsapp_message_log_id' => $outgoingLog->id,
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            if ($typingStarted) {
                $this->safelyStopTyping($sessionName, $chatId);
            }
        }
    }

    protected function safelyStartTyping(string $sessionName, string $chatId): bool
    {
        try {
            return $this->waha->startTyping($sessionName, $chatId);
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function safelyStopTyping(string $sessionName, string $chatId): void
    {
        try {
            $this->waha->stopTyping($sessionName, $chatId);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    protected function buildPrompt(
        string $userName,
        string $senderPhone,
        string $incomingMessage,
        array $context,
        array $recentConversation = [],
        ?string $additionalInstructions = null,
        string $channelLabel = 'WhatsApp',
    ): array {
        $systemPrompt = <<<PROMPT
You are impersonating {$userName} in a {$channelLabel} conversation. Reply exactly as {$userName} would. The target is not a generic similar tone. The target is the closest possible imitation of {$userName}'s real writing habits.

Rules:
- Use the recent {$channelLabel} conversation as the source of truth for what is being discussed right now
- Treat the retrieved historical messages as a style specification to imitate as closely as possible
- Match the language the sender uses (Serbian Cyrillic, Serbian Latin, or English)
- Match {$userName}'s exact writing style in the examples: casing, punctuation, spacing, abbreviations, slang, sentence fragments, emoji usage, greeting style, closings, and message length
- Preserve small stylistic details. If {$userName} usually writes in lowercase, keep lowercase. If {$userName} omits punctuation, omit it. If {$userName} uses short clipped replies, do that. If {$userName} writes in multiple short messages conceptually, reflect that rhythm in the reply text
- Do not clean up grammar, punctuation, capitalization, or wording unless the examples clearly show {$userName} does that naturally
- If unsure how {$userName} would respond, be brief and non-committal
- Never reveal you are an AI or that this is automated
- Reply with plain text only, no quotes or formatting
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        if ($additionalInstructions) {
            $messages[] = [
                'role' => 'system',
                'content' => "Additional instructions for this {$channelLabel} contact only:\n{$additionalInstructions}",
            ];
        }

        if (! empty($recentConversation)) {
            $messages = array_merge($messages, $recentConversation);
        }

        if ($context['snippets']) {
            $messages[] = [
                'role' => 'system',
                'content' => "Historical examples of how {$userName} writes. Use them as strict style imitation material, not just loose inspiration. Copy the writing habits shown there as closely as possible, including capitalization, punctuation, spacing, slang, abbreviations, rhythm, and message length. Do not treat them as facts about the current chat.\n\n{$context['snippets']}",
            ];
        }

        $messages[] = ['role' => 'user', 'content' => "New {$channelLabel} message from {$senderPhone}:\n\n{$incomingMessage}"];

        return $messages;
    }

    protected function resolveAdditionalInstructions(User $user, string $senderPhone): ?string
    {
        return $this->resolveContact($user, $senderPhone)?->ai_additional_instructions;
    }

    protected function resolveContact(User $user, string $senderPhone): ?AutoReplyContact
    {
        return AutoReplyContact::query()
            ->where('user_id', $user->id)
            ->where('channel', 'whatsapp')
            ->where('identifier', $senderPhone)
            ->first();
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
