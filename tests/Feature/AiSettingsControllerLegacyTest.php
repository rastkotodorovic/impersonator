<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAiCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ai/Index')
            ->where('providers.openai.hasCredential', false)
            ->where('providers.anthropic.hasCredential', false)
            ->where('providers.voyage.hasCredential', false)
        );
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

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ai/Index')
            ->where('providers.openai.hasCredential', true)
            ->where('providers.openai.externalEmail', 'user@example.com')
            ->where('providers.openai.authMethod', 'oauth')
        );
    }

    public function test_ai_navigation_link_exists_on_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('urls.ai', route('ai.index'))
        );
    }
}
