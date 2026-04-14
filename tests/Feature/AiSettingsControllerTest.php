<?php

namespace Tests\Feature;

use App\Models\UserAiCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_settings_page_requires_authentication(): void
    {
        $this->get(route('ai.index'))
            ->assertRedirect(route('login'));
    }

    public function test_ai_settings_page_shows_provider_options(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ai.index'));

        $response->assertOk();
        $response->assertSee('AI Settings');
        $response->assertSee('Chat provider');
        $response->assertSee('Embedding provider');
        $response->assertSee('Claude');
        $response->assertSee('Voyage');
    }

    public function test_ai_settings_page_shows_connected_credentials(): void
    {
        $user = User::factory()->create();

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'oauth',
            'access_token' => 'token-123',
            'external_email' => 'user@example.com',
        ]);

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'anthropic',
            'auth_method' => 'api_key',
            'api_key' => 'anthropic-key-1234567890',
        ]);

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'voyage',
            'auth_method' => 'api_key',
            'api_key' => 'voyage-key-1234567890',
        ]);

        $response = $this->actingAs($user)->get(route('ai.index'));

        $response->assertOk();
        $response->assertSee('Connected');
        $response->assertSee('user@example.com');
        $response->assertSee('Disconnect OpenAI');
        $response->assertSee('Disconnect Claude');
        $response->assertSee('Disconnect Voyage');
    }
}
