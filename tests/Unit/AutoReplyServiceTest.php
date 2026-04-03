<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WhatsappMessageLog;
use App\Services\AutoReplyService;
use App\Services\ChannelManager;
use App\Services\MessageRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_recent_conversation_returns_ordered_chat_history_for_contact(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $keptIncoming = WhatsappMessageLog::create([
            'user_id' => $user->id,
            'contact_phone' => '38164111222',
            'direction' => 'incoming',
            'body' => 'Where are you?',
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ]);

        WhatsappMessageLog::create([
            'user_id' => $user->id,
            'contact_phone' => '38164111222',
            'direction' => 'outgoing',
            'body' => 'On my way.',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        WhatsappMessageLog::create([
            'user_id' => $user->id,
            'contact_phone' => '38164999888',
            'direction' => 'incoming',
            'body' => 'Different contact',
        ]);

        WhatsappMessageLog::create([
            'user_id' => $otherUser->id,
            'contact_phone' => '38164111222',
            'direction' => 'incoming',
            'body' => 'Different user',
        ]);

        WhatsappMessageLog::create([
            'user_id' => $user->id,
            'contact_phone' => '38164111222',
            'direction' => 'outgoing',
            'body' => '',
            'error' => 'OpenAI failed',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $service = new TestableAutoReplyService(
            $this->mock(MessageRetrievalService::class),
            $this->mock(ChannelManager::class),
        );

        $history = $service->exposedLoadRecentConversation($user, '38164111222', $keptIncoming->id);

        $this->assertSame([
            ['role' => 'assistant', 'content' => 'On my way.'],
        ], $history);
    }

    public function test_build_prompt_includes_recent_history_and_style_examples(): void
    {
        $service = new TestableAutoReplyService(
            $this->mock(MessageRetrievalService::class),
            $this->mock(ChannelManager::class),
        );

        $messages = $service->exposedBuildPrompt(
            'Rastko',
            '38164111222',
            "Cool, let's do 8pm",
            [
                'snippets' => "Conversation with Dzil:\n[Rastko]: vazi",
                'count' => 1,
            ],
            [
                ['role' => 'user', 'content' => 'Are we still on for tonight?'],
                ['role' => 'assistant', 'content' => 'Yes, should work.'],
            ],
        );

        $this->assertCount(5, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertStringContainsString('source of truth', $messages[0]['content']);
        $this->assertSame('user', $messages[1]['role']);
        $this->assertSame('Are we still on for tonight?', $messages[1]['content']);
        $this->assertSame('assistant', $messages[2]['role']);
        $this->assertStringContainsString('style only', $messages[3]['content']);
        $this->assertSame('user', $messages[4]['role']);
        $this->assertStringContainsString("Cool, let's do 8pm", $messages[4]['content']);
    }
}

class TestableAutoReplyService extends AutoReplyService
{
    public function exposedLoadRecentConversation(User $user, string $senderPhone, ?int $excludeLogId = null): array
    {
        return $this->loadRecentConversation($user, $senderPhone, $excludeLogId);
    }

    public function exposedBuildPrompt(
        string $userName,
        string $senderPhone,
        string $incomingMessage,
        array $context,
        array $recentConversation = [],
    ): array {
        return $this->buildPrompt($userName, $senderPhone, $incomingMessage, $context, $recentConversation);
    }
}
