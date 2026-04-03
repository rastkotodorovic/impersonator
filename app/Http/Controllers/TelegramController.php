<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramAutoReply;
use App\Models\AutoReplyContact;
use App\Models\TelegramBot;
use App\Models\TelegramMessageLog;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TelegramController extends Controller
{
    public function __construct(
        protected TelegramService $telegram,
    ) {}

    public function index(Request $request): View
    {
        return view('telegram.index', [
            'bot' => $request->user()->telegramBot,
            'recentLogs' => TelegramMessageLog::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bot_token' => ['required', 'string'],
        ]);

        $botData = $this->telegram->getMe($validated['bot_token']);
        $secret = Str::random(40);

        $this->telegram->setWebhook(
            $validated['bot_token'],
            route('telegram.webhook', ['secret' => $secret]),
        );

        $request->user()->telegramBot()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'bot_token' => $validated['bot_token'],
                'bot_id' => (string) ($botData['id'] ?? ''),
                'bot_username' => $botData['username'] ?? null,
                'webhook_secret' => $secret,
                'status' => 'connected',
                'connected_at' => now(),
                'metadata' => $botData,
            ],
        );

        return back()->with('success', 'Telegram bot connected.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $bot = $request->user()->telegramBot;

        if ($bot) {
            $this->telegram->deleteWebhook($bot->bot_token);
            $bot->update(['status' => 'disconnected']);
        }

        return back()->with('success', 'Telegram bot disconnected.');
    }

    public function webhook(Request $request, string $secret): JsonResponse
    {
        $bot = TelegramBot::query()
            ->where('webhook_secret', $secret)
            ->first();

        if (! $bot || ! $bot->isConnected()) {
            return response()->json(['ok' => true]);
        }

        $message = $request->input('message');

        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $text = $message['text'] ?? null;
        $chatId = (string) ($message['chat']['id'] ?? '');
        $messageId = isset($message['message_id']) ? (string) $message['message_id'] : null;

        if (! $text || ! $chatId) {
            return response()->json(['ok' => true]);
        }

        if ($messageId && TelegramMessageLog::where('telegram_message_id', $messageId)->exists()) {
            return response()->json(['ok' => true]);
        }

        $from = $message['from'] ?? [];
        $username = isset($from['username']) ? (string) $from['username'] : null;

        $contact = null;

        if ($username) {
            $contact = AutoReplyContact::query()
                ->where('user_id', $bot->user_id)
                ->where('channel', 'telegram')
                ->where('identifier', AutoReplyContact::normalizeIdentifier('telegram', $username))
                ->where('is_active', true)
                ->first();
        }

        if (! $contact) {
            $contact = AutoReplyContact::query()
                ->where('user_id', $bot->user_id)
                ->where('channel', 'telegram')
                ->where('identifier', $chatId)
                ->where('is_active', true)
                ->first();
        }

        if (! $contact) {
            return response()->json(['ok' => true]);
        }

        $user = $bot->user;

        if (! $user->openaiCredential?->hasValidCredential()) {
            return response()->json(['ok' => true]);
        }

        $recentReply = TelegramMessageLog::query()
            ->where('user_id', $user->id)
            ->where('chat_id', $chatId)
            ->where('direction', 'outgoing')
            ->where('created_at', '>', now()->subSeconds(5))
            ->exists();

        if ($recentReply) {
            return response()->json(['ok' => true]);
        }

        $contactName = trim(($from['first_name'] ?? '').' '.($from['last_name'] ?? '')) ?: $username;

        ProcessTelegramAutoReply::dispatch(
            $user->id,
            $bot->id,
            $chatId,
            $contact->identifier,
            $contactName ?: null,
            $text,
            $messageId,
        );

        return response()->json(['ok' => true]);
    }
}
