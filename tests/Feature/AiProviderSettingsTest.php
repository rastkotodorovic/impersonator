<?php

namespace Tests\Feature;

use App\Models\UserAiCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiProviderSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_anthropic_api_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.anthropic.api-key.store'), [
            'anthropic_api_key' => 'anthropic-key-1234567890',
        ]);

        $response->assertRedirect(route('ai.index'));
        $this->assertDatabaseHas('user_ai_credentials', [
            'user_id' => $user->id,
            'provider' => 'anthropic',
        ]);
    }

    public function test_save_voyage_api_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.voyage.api-key.store'), [
            'voyage_api_key' => 'voyage-key-1234567890',
        ]);

        $response->assertRedirect(route('ai.index'));
        $this->assertDatabaseHas('user_ai_credentials', [
            'user_id' => $user->id,
            'provider' => 'voyage',
        ]);
    }

    public function test_save_provider_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.providers.update'), [
            'chat_provider' => 'anthropic',
            'embedding_provider' => 'voyage',
        ]);

        $response->assertRedirect(route('ai.index'));

        $user->refresh();

        $this->assertSame('anthropic', $user->chat_provider);
        $this->assertSame('voyage', $user->embedding_provider);
    }

    public function test_remove_provider_credentials(): void
    {
        $user = User::factory()->create();

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

        $this->actingAs($user)->delete(route('ai.anthropic.credential.destroy'))
            ->assertRedirect(route('ai.index'));

        $this->actingAs($user)->delete(route('ai.voyage.credential.destroy'))
            ->assertRedirect(route('ai.index'));

        $this->assertDatabaseMissing('user_ai_credentials', ['user_id' => $user->id, 'provider' => 'anthropic']);
        $this->assertDatabaseMissing('user_ai_credentials', ['user_id' => $user->id, 'provider' => 'voyage']);
    }

    public function test_openai_provider_routes_work_under_ai_namespace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.openai.api-key.store'), [
            'api_key' => 'sk-test-key-12345',
        ]);

        $response->assertRedirect(route('ai.index'));
        $this->assertDatabaseHas('user_ai_credentials', [
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'api_key',
        ]);

        $credential = $user->fresh()->aiCredentialFor('openai');
        $this->assertEquals('sk-test-key-12345', $credential->api_key);
    }
}
