<?php

namespace Tests\Unit;

use App\Integrations\Waha\WahaService;
use App\Models\AutoReplyContact;
use App\Models\Conversation;
use App\Models\UserAiCredential;
use App\Models\User;
use App\Models\WhatsappMessageLog;
use App\Services\Ai\ChatProviderManager;
use App\Services\AutoReplyService;
use App\Services\ChannelManager;
use App\Services\Channels\WhatsappChannel;
use App\Services\MessageRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        $this->assertStringContainsString('closest possible imitation', $messages[0]['content']);
        $this->assertStringContainsString('casing, punctuation, spacing', $messages[0]['content']);
        $this->assertSame('user', $messages[1]['role']);
        $this->assertSame('Are we still on for tonight?', $messages[1]['content']);
        $this->assertSame('assistant', $messages[2]['role']);
        $this->assertStringContainsString('strict style imitation material', $messages[3]['content']);
        $this->assertSame('user', $messages[4]['role']);
        $this->assertStringContainsString("Cool, let's do 8pm", $messages[4]['content']);
    }

    public function test_build_prompt_includes_contact_specific_instructions_when_present(): void
    {
        $service = new TestableAutoReplyService(
            $this->mock(MessageRetrievalService::class),
            $this->mock(ChannelManager::class),
        );

        $messages = $service->exposedBuildPrompt(
            'Rastko',
            '38164111222',
            'Can we talk tomorrow?',
            [
                'snippets' => '',
                'count' => 0,
            ],
            [],
            'Reply in Serbian Latin and keep it brief.',
        );

        $this->assertCount(3, $messages);
        $this->assertSame('system', $messages[1]['role']);
        $this->assertStringContainsString('Additional instructions', $messages[1]['content']);
        $this->assertStringContainsString('keep it brief', $messages[1]['content']);
    }

    public function test_resolve_additional_instructions_returns_contact_prompt(): void
    {
        $user = User::factory()->create();

        AutoReplyContact::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'phone_number' => '38164111222',
            'identifier' => '38164111222',
            'name' => 'VIP',
            'ai_additional_instructions' => 'Treat as VIP and answer formally.',
            'is_active' => true,
        ]);

        $service = new TestableAutoReplyService(
            $this->mock(MessageRetrievalService::class),
            $this->mock(ChannelManager::class),
        );

        $this->assertSame(
            'Treat as VIP and answer formally.',
            $service->exposedResolveAdditionalInstructions($user, '38164111222')
        );
    }

    public function test_generate_and_send_reply_prioritizes_contact_preferred_conversation(): void
    {
        config()->set('services.waha.api_url', 'http://waha.test');
        config()->set('services.waha.api_key', null);

        $user = User::factory()->create();
        $conversation = Conversation::create([
            'thread_path' => 'inbox/preferred-thread',
            'title' => 'Preferred Style Chat',
            'source' => 'inbox',
            'participants' => ['Rastko', 'Dzil'],
            'participant_count' => 2,
            'is_group_chat' => false,
        ]);

        AutoReplyContact::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'phone_number' => '38164111222',
            'identifier' => '38164111222',
            'preferred_conversation_id' => $conversation->id,
            'is_active' => true,
        ]);

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'api_key',
            'api_key' => 'sk-test-key-12345',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'On my way.']],
                ],
                'usage' => ['total_tokens' => 12],
                'model' => 'gpt-4o',
            ]),
            'http://waha.test/api/default/presence' => Http::sequence()
                ->push(['success' => true], 200)
                ->push(['success' => true], 200),
            'http://waha.test/api/sendText' => Http::response(['id' => 'msg-1'], 200),
        ]);

        $retrieval = $this->mock(MessageRetrievalService::class, function ($mock) use ($user, $conversation) {
            $mock->shouldReceive('retrieveContext')
                ->once()
                ->with('Where are you?', $user, 15, $conversation->id)
                ->andReturn([
                    'count' => 0,
                    'hits' => [],
                    'snippet_blocks' => [],
                    'snippets' => '',
                ]);
        });

        $service = new TestableAutoReplyService(
            $retrieval,
            new ChannelManager(new WhatsappChannel(new WahaService)),
            new WahaService,
        );

        $service->generateAndSendReply($user, '38164111222', 'Where are you?', 'default');
    }

    public function test_generate_and_send_reply_toggles_typing_presence_around_reply(): void
    {
        config()->set('services.waha.api_url', 'http://waha.test');
        config()->set('services.waha.api_key', null);

        $user = User::factory()->create();

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'api_key',
            'api_key' => 'sk-test-key-12345',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'On my way.']],
                ],
                'usage' => ['total_tokens' => 12],
                'model' => 'gpt-4o',
            ]),
            'http://waha.test/api/default/presence' => Http::sequence()
                ->push(['success' => true], 200)
                ->push(['success' => true], 200),
            'http://waha.test/api/sendText' => Http::response(['id' => 'msg-1'], 200),
        ]);

        $retrieval = $this->mock(MessageRetrievalService::class, function ($mock) use ($user) {
            $mock->shouldReceive('retrieveContext')
                ->once()
                ->with('Where are you?', $user, 15, null)
                ->andReturn([
                    'count' => 0,
                    'hits' => [],
                    'snippet_blocks' => [],
                    'snippets' => '',
                ]);
        });

        $service = new TestableAutoReplyService(
            $retrieval,
            new ChannelManager(new WhatsappChannel(new WahaService)),
            new WahaService,
        );

        $outgoing = $service->generateAndSendReply($user, '38164111222', 'Where are you?', 'default');

        $this->assertSame('outgoing', $outgoing->direction);
        $this->assertSame('On my way.', $outgoing->body);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://waha.test/api/default/presence'
                && $request['chatId'] === '38164111222@s.whatsapp.net'
                && $request['presence'] === 'typing';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'http://waha.test/api/sendText'
                && $request['session'] === 'default'
                && $request['chatId'] === '38164111222@s.whatsapp.net'
                && $request['text'] === 'On my way.';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'http://waha.test/api/default/presence'
                && $request['chatId'] === '38164111222@s.whatsapp.net'
                && $request['presence'] === 'paused';
        });
    }
}

class TestableAutoReplyService extends AutoReplyService
{
    public function __construct(
        MessageRetrievalService $retrieval,
        ChannelManager $channels,
        ?WahaService $waha = null,
    ) {
        parent::__construct($retrieval, new ChatProviderManager, $channels, $waha ?? new WahaService);
    }

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
        ?string $additionalInstructions = null,
    ): array {
        return $this->buildPrompt($userName, $senderPhone, $incomingMessage, $context, $recentConversation, $additionalInstructions);
    }

    public function exposedResolveAdditionalInstructions(User $user, string $senderPhone): ?string
    {
        return $this->resolveAdditionalInstructions($user, $senderPhone);
    }
}
