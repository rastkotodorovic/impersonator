<?php

namespace App\Services\Ai;

use App\Contracts\ChatCompletionProviderInterface;
use App\Integrations\Anthropic\AnthropicService;
use App\Integrations\OpenAI\OpenAIService;
use App\Models\User;

class ChatProviderManager
{
    public function forUser(User $user): ChatCompletionProviderInterface
    {
        return match ($user->chat_provider ?? config('services.ai.default_chat_provider', 'openai')) {
            'anthropic' => AnthropicService::forUser($user),
            default => OpenAIService::forUser($user),
        };
    }

    public function hasConfiguredProvider(User $user): bool
    {
        return match ($user->chat_provider ?? config('services.ai.default_chat_provider', 'openai')) {
            'anthropic' => $user->aiCredentialFor('anthropic')?->hasValidCredential() ?? false,
            default => $user->aiCredentialFor('openai')?->hasValidCredential() ?? false,
        };
    }
}
