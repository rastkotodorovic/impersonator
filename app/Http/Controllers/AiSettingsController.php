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
            'hasOpenAiCredential' => $openAiCredential && $openAiCredential->hasValidCredential(),
            'hasAnthropicCredential' => $anthropicCredential && $anthropicCredential->hasValidCredential(),
            'hasVoyageCredential' => $voyageCredential && $voyageCredential->hasValidCredential(),
        ]);
    }
}
