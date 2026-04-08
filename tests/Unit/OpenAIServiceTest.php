<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserOpenaiCredential;
use App\Integrations\OpenAI\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_embeddings_use_stored_api_key_when_present(): void
    {
        config()->set('services.openai.api_key', null);

        $user = User::factory()->create();

        UserOpenaiCredential::create([
            'user_id' => $user->id,
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
        config()->set('services.openai.api_key', null);

        $user = User::factory()->create();

        UserOpenaiCredential::create([
            'user_id' => $user->id,
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
}
