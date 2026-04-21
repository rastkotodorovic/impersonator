<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAiCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ai/Index')
            ->where('providerSelection.chat_provider', 'openai')
            ->where('providerSelection.embedding_provider', 'openai')
            ->where('providers.openai.hasCredential', false)
            ->where('providers.anthropic.hasCredential', false)
            ->where('providers.voyage.hasCredential', false)
            ->where('urls.providers', route('ai.providers.update'))
            ->where('urls.openaiRedirect', route('ai.openai.redirect'))
        );
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

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ai/Index')
            ->where('providers.openai.hasCredential', true)
            ->where('providers.openai.externalEmail', 'user@example.com')
            ->where('providers.anthropic.hasCredential', true)
            ->where('providers.voyage.hasCredential', true)
        );
    }
}
