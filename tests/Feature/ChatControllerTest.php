<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserOpenaiCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_page_shows_setup_when_no_credential(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('chat.index'));

        $response->assertOk();
        $response->assertSee('Connect to OpenAI');
        $response->assertSee('API Key');
        $response->assertSee('Sign in with OpenAI');
    }

    public function test_chat_page_shows_chat_when_credential_exists(): void
    {
        $user = User::factory()->create();
        UserOpenaiCredential::create([
            'user_id' => $user->id,
            'auth_method' => 'api_key',
            'api_key' => 'sk-test-key-12345',
        ]);

        $response = $this->actingAs($user)->get(route('chat.index'));

        $response->assertOk();
        $response->assertSee('Send a message to start chatting');
        $response->assertSee('GPT-4o');
    }

    public function test_chat_page_requires_authentication(): void
    {
        $this->get(route('chat.index'))
            ->assertRedirect(route('login'));
    }

    public function test_send_requires_authentication(): void
    {
        $this->post(route('chat.send'), [
            'message' => 'Hello',
        ])->assertRedirect(route('login'));
    }

    public function test_send_validates_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('chat.send'), [
            'message' => '',
        ])->assertSessionHasErrors('message');
    }

    public function test_send_validates_history_roles(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('chat.send'), [
            'message' => 'Hello',
            'history' => [
                ['role' => 'tool', 'content' => 'invalid'],
            ],
        ])->assertSessionHasErrors('history.0.role');
    }

    public function test_chat_navigation_link_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSee('Chat');
    }

    public function test_chat_page_shows_credential_info(): void
    {
        $user = User::factory()->create();
        UserOpenaiCredential::create([
            'user_id' => $user->id,
            'auth_method' => 'oauth',
            'oauth_access_token' => 'token-123',
            'openai_email' => 'user@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('chat.index'));

        $response->assertOk();
        $response->assertSee('user@example.com');
        $response->assertSee('Disconnect');
    }
}
