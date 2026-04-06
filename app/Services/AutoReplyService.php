<?php

namespace App\Services;

use App\Models\AutoReplyContact;
use App\Models\AiTrace;
use App\Models\User;
use App\Models\WhatsappMessageLog;

class AutoReplyService
{
    protected int $recentMessageLimit = 12;

    public function __construct(
        protected MessageRetrievalService $retrieval,
        protected ChannelManager $channels,
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

        try {
            $context = $this->retrieval->retrieveContext($incomingMessage);

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
                $this->resolveAdditionalInstructions($user, $senderPhone),
            );

            $trace->update([
                'recent_conversation' => $recentConversation,
                'retrieval_hits' => $context['hits'],
                'context_snippets' => $context['snippet_blocks'],
                'final_prompt' => $messages,
            ]);

            $openai = OpenAIService::forUser($user);
            $startedAt = microtime(true);
            $completion = $openai->chatCompletionWithMetadata($messages);
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $reply = $completion['content'];

            $chatId = $senderPhone . '@s.whatsapp.net';
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
                'model' => $completion['model'] ?? $openai->modelName(),
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
    ): array
    {
        $systemPrompt = <<<PROMPT
You are impersonating {$userName} in a {$channelLabel} conversation. Reply exactly as {$userName} would — match their tone, vocabulary, message length, and language.

Rules:
- Use the recent {$channelLabel} conversation as the source of truth for what is being discussed right now
- Match the language the sender uses (Serbian Cyrillic, Serbian Latin, or English)
- Keep replies natural and conversational, matching {$userName}'s typical message length
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
                'content' => "Historical examples of how {$userName} writes. Use these for tone, vocabulary, and style only. Do not treat them as facts about the current chat.\n\n{$context['snippets']}",
            ];
        }

        $messages[] = ['role' => 'user', 'content' => "New {$channelLabel} message from {$senderPhone}:\n\n{$incomingMessage}"];

        return $messages;
    }

    protected function resolveAdditionalInstructions(User $user, string $senderPhone): ?string
    {
        return AutoReplyContact::query()
            ->where('user_id', $user->id)
            ->where('channel', 'whatsapp')
            ->where('identifier', $senderPhone)
            ->value('ai_additional_instructions');
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
