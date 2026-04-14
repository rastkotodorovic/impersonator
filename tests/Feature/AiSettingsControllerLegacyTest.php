<?php

namespace Tests\Feature;

use App\Models\UserAiCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSettingsControllerLegacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_settings_page_requires_authentication(): void
    {
        $this->get(route('ai.index'))
            ->assertRedirect(route('login'));
    }

    public function test_ai_settings_page_shows_connect_options_when_no_credential(): void
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

    public function test_ai_settings_page_shows_connected_credential_details(): void
    {
        $user = User::factory()->create();

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'oauth',
            'access_token' => 'token-123',
            'external_email' => 'user@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('ai.index'));

        $response->assertOk();
        $response->assertSee('Provider Selection');
        $response->assertSee('Connected');
        $response->assertSee('user@example.com');
        $response->assertSee('Disconnect OpenAI');
    }

    public function test_ai_navigation_link_exists_on_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSee('AI');
        $response->assertSee(route('ai.index'), false);
    }
}
