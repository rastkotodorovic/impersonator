<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $user->loadMissing('aiCredentials');
        $openAiCredential = $user->aiCredentialFor('openai');
        $anthropicCredential = $user->aiCredentialFor('anthropic');
        $voyageCredential = $user->aiCredentialFor('voyage');

        return Inertia::render('Ai/Index', [
            'providerSelection' => [
                'chat_provider' => $user->chat_provider ?? config('services.ai.default_chat_provider', 'openai'),
                'embedding_provider' => $user->embedding_provider ?? config('services.ai.default_embedding_provider', 'openai'),
            ],
            'providers' => [
                'openai' => [
                    'hasCredential' => $openAiCredential && $openAiCredential->hasValidCredential(),
                    'authMethod' => $openAiCredential?->auth_method,
                    'externalEmail' => $openAiCredential?->external_email,
                    'apiKeySuffix' => $openAiCredential?->isApiKey() && $openAiCredential->getActiveToken()
                        ? substr($openAiCredential->getActiveToken(), -4)
                        : null,
                    'chatModel' => $openAiCredential?->getMetadataValue('chat_model', 'gpt-4o'),
                    'embeddingModel' => $openAiCredential?->getMetadataValue('embedding_model', 'text-embedding-3-small'),
                ],
                'anthropic' => [
                    'hasCredential' => $anthropicCredential && $anthropicCredential->hasValidCredential(),
                    'chatModel' => $anthropicCredential?->getMetadataValue('chat_model', 'claude-3-7-sonnet-latest'),
                ],
                'voyage' => [
                    'hasCredential' => $voyageCredential && $voyageCredential->hasValidCredential(),
                    'embeddingModel' => $voyageCredential?->getMetadataValue('embedding_model', 'voyage-3-lite'),
                ],
            ],
            'urls' => [
                'dashboard' => route('dashboard'),
                'profile' => route('profile.edit'),
                'whatsapp' => route('whatsapp.index'),
                'imports' => route('imports.index'),
                'ai' => route('ai.index'),
                'providers' => route('ai.providers.update'),
                'openaiRedirect' => route('ai.openai.redirect'),
                'openaiApiKey' => route('ai.openai.api-key.store'),
                'openaiModels' => route('ai.openai.models.store'),
                'openaiDisconnect' => route('ai.openai.credential.destroy'),
                'anthropicApiKey' => route('ai.anthropic.api-key.store'),
                'anthropicModels' => route('ai.anthropic.models.store'),
                'anthropicDisconnect' => route('ai.anthropic.credential.destroy'),
                'voyageApiKey' => route('ai.voyage.api-key.store'),
                'voyageModels' => route('ai.voyage.models.store'),
                'voyageDisconnect' => route('ai.voyage.credential.destroy'),
            ],
        ]);
    }
}
