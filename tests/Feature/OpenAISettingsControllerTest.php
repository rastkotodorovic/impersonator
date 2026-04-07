<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserOpenaiCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenAISettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_openai_settings_page_requires_authentication(): void
    {
        $this->get(route('openai.index'))
            ->assertRedirect(route('login'));
    }

    public function test_openai_settings_page_shows_connect_options_when_no_credential(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('openai.index'));

        $response->assertOk();
        $response->assertSee('OpenAI Settings');
        $response->assertSee('Connect OpenAI');
        $response->assertSee('API Key');
        $response->assertSee('Sign in with OpenAI');
        $response->assertSee('No OpenAI credential connected');
    }

    public function test_openai_settings_page_shows_connected_credential_details(): void
    {
        $user = User::factory()->create();

        UserOpenaiCredential::create([
            'user_id' => $user->id,
            'auth_method' => 'oauth',
            'oauth_access_token' => 'token-123',
            'openai_email' => 'user@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('openai.index'));

        $response->assertOk();
        $response->assertSee('Current Connection');
        $response->assertSee('Connected');
        $response->assertSee('user@example.com');
        $response->assertSee('Disconnect');
    }

    public function test_openai_navigation_link_exists_on_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSee('OpenAI');
        $response->assertSee(route('openai.index'), false);
    }
}
