<?php

namespace Tests\Feature;

use App\Models\AiTrace;
use App\Models\User;
use App\Models\WhatsappMessageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiTraceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_renders_ai_trace_page(): void
    {
        $user = User::factory()->create();

        $log = WhatsappMessageLog::create([
            'user_id' => $user->id,
            'contact_phone' => '38164111222',
            'direction' => 'outgoing',
            'body' => 'Hey, I can do tomorrow after 3.',
            'context_messages_used' => 4,
        ]);

        AiTrace::create([
            'user_id' => $user->id,
            'contact_phone' => '38164111222',
            'outgoing_whatsapp_message_log_id' => $log->id,
            'status' => 'completed',
            'input_message' => 'Are you free tomorrow?',
            'retrieval_query' => 'free tomorrow afternoon short reply',
            'recent_conversation' => [
                ['role' => 'user', 'content' => 'Are you around later?'],
                ['role' => 'assistant', 'content' => 'Yes, after 3 works for me.'],
            ],
            'retrieval_hits' => [
                [
                    'rank' => 1,
                    'conversation_title' => 'Dzil',
                    'sender_name' => 'Rastko',
                    'sent_at' => '2026-04-20 15:30',
                    'ranking_score' => 0.987,
                    'content' => 'I can do tomorrow after 3.',
                ],
            ],
            'context_snippets' => [
                [
                    'conversation_title' => 'Dzil',
                    'matched_message' => [
                        'sender_name' => 'Rastko',
                        'ranking_score' => 0.987,
                    ],
                    'messages' => [
                        ['sender_name' => 'Dzil', 'content' => 'Can you tomorrow?'],
                        ['sender_name' => 'Rastko', 'content' => 'Yes, after 3.'],
                    ],
                ],
            ],
            'final_prompt' => [
                ['role' => 'system', 'content' => 'Reply briefly and casually.'],
                ['role' => 'user', 'content' => 'Are you free tomorrow?'],
            ],
            'model' => 'gpt-5.4-mini',
            'model_response' => 'Yeah, I can after 3.',
            'usage' => [
                'prompt_tokens' => 120,
                'completion_tokens' => 18,
                'total_tokens' => 138,
            ],
            'latency_ms' => 842,
        ]);

        $response = $this->actingAs($user)->get(route('whatsapp.auto-reply.logs.trace', $log));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Whatsapp/AiTrace')
            ->where('log.id', $log->id)
            ->where('trace.contact_phone', '38164111222')
            ->where('trace.status', 'completed')
            ->where('trace.retrieval_hits_count', 1)
            ->where('trace.prompt_messages_count', 2)
            ->where('trace.usage.total_tokens', 138)
            ->where('urls.autoReply', route('whatsapp.auto-reply.index'))
            ->has('trace.recent_conversation', 2)
            ->has('trace.context_snippets', 1)
            ->has('trace.final_prompt', 2)
        );
    }
}
