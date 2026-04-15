<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $user->loadMissing('aiCredentials');
        $openAiCredential = $user->aiCredentialFor('openai');
        $anthropicCredential = $user->aiCredentialFor('anthropic');
        $voyageCredential = $user->aiCredentialFor('voyage');

        return view('ai.index', [
            'chatProvider' => $user->chat_provider ?? config('services.ai.default_chat_provider', 'openai'),
            'embeddingProvider' => $user->embedding_provider ?? config('services.ai.default_embedding_provider', 'openai'),
            'openAiCredential' => $openAiCredential,
            'anthropicCredential' => $anthropicCredential,
            'voyageCredential' => $voyageCredential,
            'openAiChatModel' => $openAiCredential?->getMetadataValue('chat_model', 'gpt-4o'),
            'openAiEmbeddingModel' => $openAiCredential?->getMetadataValue('embedding_model', 'text-embedding-3-small'),
            'anthropicChatModel' => $anthropicCredential?->getMetadataValue('chat_model', 'claude-3-7-sonnet-latest'),
            'voyageEmbeddingModel' => $voyageCredential?->getMetadataValue('embedding_model', 'voyage-3-lite'),
            'hasOpenAiCredential' => $openAiCredential && $openAiCredential->hasValidCredential(),
            'hasAnthropicCredential' => $anthropicCredential && $anthropicCredential->hasValidCredential(),
            'hasVoyageCredential' => $voyageCredential && $voyageCredential->hasValidCredential(),
        ]);
    }
}
