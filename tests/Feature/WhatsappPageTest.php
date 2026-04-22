<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WhatsappPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_page_requires_authentication(): void
    {
        $this->get(route('whatsapp.index'))
            ->assertRedirect(route('login'));
    }

    public function test_whatsapp_page_renders_inertia_payload_without_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('whatsapp.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Whatsapp/Index')
            ->where('session', null)
            ->where('urls.whatsapp', route('whatsapp.index'))
            ->where('urls.autoReply', route('whatsapp.auto-reply.index'))
        );
    }

    public function test_whatsapp_page_includes_existing_session_details(): void
    {
        $user = User::factory()->create();

        WhatsappSession::create([
            'user_id' => $user->id,
            'session_name' => 'default',
            'status' => 'connected',
            'phone_number' => '381651234567',
            'display_name' => 'Codex Device',
            'connected_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)->get(route('whatsapp.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Whatsapp/Index')
            ->where('session.status', 'connected')
            ->where('session.phone_number', '381651234567')
            ->where('session.display_name', 'Codex Device')
            ->has('session.connected_at')
        );
    }
}
