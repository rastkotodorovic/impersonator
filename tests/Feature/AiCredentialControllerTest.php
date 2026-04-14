<?php

namespace Tests\Feature;

use App\Models\UserAiCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCredentialControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_api_key(): void
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

    public function test_api_key_must_start_with_sk(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.openai.api-key.store'), [
            'api_key' => 'invalid-key',
        ]);

        $response->assertSessionHasErrors('api_key');
    }

    public function test_api_key_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.openai.api-key.store'), [
            'api_key' => '',
        ]);

        $response->assertSessionHasErrors('api_key');
    }

    public function test_remove_credential(): void
    {
        $user = User::factory()->create();
        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'api_key',
            'api_key' => 'sk-test-key-12345',
        ]);

        $response = $this->actingAs($user)->delete(route('ai.openai.credential.destroy'));

        $response->assertRedirect(route('ai.index'));
        $this->assertDatabaseMissing('user_ai_credentials', [
            'user_id' => $user->id,
            'provider' => 'openai',
        ]);
    }

    public function test_oauth_redirect(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ai.openai.redirect'));

        $response->assertRedirect();
        $this->assertStringContainsString('auth.openai.com', $response->headers->get('Location'));
    }

    public function test_unauthenticated_users_cannot_access_routes(): void
    {
        $this->post(route('ai.openai.api-key.store'), ['api_key' => 'sk-test'])
            ->assertRedirect(route('login'));

        $this->get(route('ai.openai.redirect'))
            ->assertRedirect(route('login'));

        $this->delete(route('ai.openai.credential.destroy'))
            ->assertRedirect(route('login'));
    }

    public function test_save_api_key_replaces_existing_credential(): void
    {
        $user = User::factory()->create();
        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'api_key',
            'api_key' => 'sk-old-key',
        ]);

        $this->actingAs($user)->post(route('ai.openai.api-key.store'), [
            'api_key' => 'sk-new-key-67890',
        ]);

        $credential = $user->fresh()->aiCredentialFor('openai');
        $this->assertEquals('sk-new-key-67890', $credential->api_key);
        $this->assertCount(1, UserAiCredential::where('user_id', $user->id)->where('provider', 'openai')->get());
    }
}
