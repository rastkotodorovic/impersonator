<?php

namespace App\Services;

use App\Models\AutoReplyContact;
use App\Models\TelegramBot;
use App\Models\TelegramMessageLog;
use App\Models\User;

class TelegramAutoReplyService
{
    protected int $recentMessageLimit = 12;

    public function __construct(
        protected MessageRetrievalService $retrieval,
        protected ChannelManager $channels,
    ) {}

    public function generateAndSendReply(
        User $user,
        int $telegramBotId,
        string $chatId,
        string $contactIdentifier,
        string $incomingMessage,
        ?string $contactName = null,
        ?string $telegramMessageId = null,
    ): TelegramMessageLog {
        $incomingLog = TelegramMessageLog::create([
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'contact_identifier' => $contactIdentifier,
            'contact_name' => $contactName,
            'direction' => 'incoming',
            'body' => $incomingMessage,
            'telegram_message_id' => $telegramMessageId,
        ]);

        $context = $this->retrieval->retrieveContext($incomingMessage);
        $recentConversation = $this->loadRecentConversation($user, $chatId, $incomingLog->id);
        $messages = $this->buildPrompt(
            $user->name,
            $contactIdentifier,
            $incomingMessage,
            $context,
            $recentConversation,
            $this->resolveAdditionalInstructions($user, $contactIdentifier),
        );

        $openai = OpenAIService::forUser($user);
        $completion = $openai->chatCompletionWithMetadata($messages);
        $reply = $completion['content'];

        $bot = TelegramBot::findOrFail($telegramBotId);
        $this->channels->for('telegram')->sendMessage($bot->bot_token, $chatId, $reply);

        return TelegramMessageLog::create([
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'contact_identifier' => $contactIdentifier,
            'contact_name' => $contactName,
            'direction' => 'outgoing',
            'body' => $reply,
            'context_messages_used' => $context['count'],
        ]);
    }

    protected function buildPrompt(
        string $userName,
        string $contactIdentifier,
        string $incomingMessage,
        array $context,
        array $recentConversation = [],
        ?string $additionalInstructions = null,
    ): array {
        $systemPrompt = <<<PROMPT
You are impersonating {$userName} in a Telegram conversation. Reply exactly as {$userName} would — match their tone, vocabulary, message length, and language.

Rules:
- Use the recent Telegram conversation as the source of truth for what is being discussed right now
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
                'content' => "Additional instructions for this Telegram contact only:\n{$additionalInstructions}",
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

        $messages[] = ['role' => 'user', 'content' => "New Telegram message from {$contactIdentifier}:\n\n{$incomingMessage}"];

        return $messages;
    }

    protected function resolveAdditionalInstructions(User $user, string $contactIdentifier): ?string
    {
        return AutoReplyContact::query()
            ->where('user_id', $user->id)
            ->where('channel', 'telegram')
            ->where('identifier', $contactIdentifier)
            ->value('ai_additional_instructions');
    }

    protected function loadRecentConversation(User $user, string $chatId, ?int $excludeLogId = null): array
    {
        $logs = TelegramMessageLog::query()
            ->where('user_id', $user->id)
            ->where('chat_id', $chatId)
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
            ->map(fn (TelegramMessageLog $log) => [
                'role' => $log->direction === 'incoming' ? 'user' : 'assistant',
                'content' => $log->body,
            ])
            ->all();
    }
}
