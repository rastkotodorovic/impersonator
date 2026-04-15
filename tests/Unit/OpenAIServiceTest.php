<?php

namespace Tests\Unit;

use App\Integrations\OpenAI\OpenAIService;
use App\Models\User;
use App\Models\UserAiCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_embeddings_use_stored_api_key_when_present(): void
    {
        $user = User::factory()->create();

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'api_key',
            'api_key' => 'sk-test-key-12345',
        ]);

        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => [0.1, 0.2, 0.3]],
                ],
            ]),
        ]);

        $result = OpenAIService::forEmbeddings()->embeddings(['hello']);

        $this->assertSame([[0.1, 0.2, 0.3]], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/embeddings'
                && $request->hasHeader('Authorization', 'Bearer sk-test-key-12345');
        });
    }

    public function test_embeddings_can_use_specific_users_stored_credential(): void
    {
        $user = User::factory()->create();

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'api_key',
            'api_key' => 'sk-user-key-67890',
        ]);

        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => [0.5, 0.6]],
                ],
            ]),
        ]);

        $result = OpenAIService::forEmbeddings($user)->embeddings(['query']);

        $this->assertSame([[0.5, 0.6]], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/embeddings'
                && $request->hasHeader('Authorization', 'Bearer sk-user-key-67890');
        });
    }

    public function test_embeddings_use_users_configured_embedding_model(): void
    {
        $user = User::factory()->create();

        UserAiCredential::create([
            'user_id' => $user->id,
            'provider' => 'openai',
            'auth_method' => 'api_key',
            'api_key' => 'sk-user-key-67890',
            'metadata' => [
                'embedding_model' => 'text-embedding-3-large',
            ],
        ]);

        Http::fake([
            'https://api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => [0.5, 0.6]],
                ],
            ]),
        ]);

        OpenAIService::forEmbeddings($user)->embeddings(['query']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/embeddings'
                && $request['model'] === 'text-embedding-3-large';
        });
    }

    public function test_embeddings_require_dashboard_configured_credential(): void
    {
        $this->expectExceptionMessage('Save an OpenAI credential in AI settings.');

        OpenAIService::forEmbeddings()->embeddings(['query']);
    }
}
