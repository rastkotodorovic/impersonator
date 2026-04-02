<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveOpenAiApiKeyRequest;
use App\Models\UserOpenaiCredential;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class OpenAIAuthController extends Controller
{
    public function redirect(): RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('openai')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $openaiUser = Socialite::driver('openai')->user();

        UserOpenaiCredential::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'auth_method' => 'oauth',
                'api_key' => null,
                'oauth_access_token' => $openaiUser->token,
                'oauth_refresh_token' => $openaiUser->refreshToken,
                'oauth_token_expires_at' => $openaiUser->expiresIn
                    ? now()->addSeconds($openaiUser->expiresIn)
                    : null,
                'openai_user_id' => $openaiUser->getId(),
                'openai_email' => $openaiUser->getEmail(),
            ]
        );

        return redirect()->route('chat.index')->with('success', 'OpenAI account connected.');
    }

    public function saveApiKey(SaveOpenAiApiKeyRequest $request): RedirectResponse
    {
        UserOpenaiCredential::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'auth_method' => 'api_key',
                'api_key' => $request->validated('api_key'),
                'oauth_access_token' => null,
                'oauth_refresh_token' => null,
                'oauth_token_expires_at' => null,
                'openai_user_id' => null,
                'openai_email' => null,
            ]
        );

        return redirect()->route('chat.index')->with('success', 'API key saved.');
    }

    public function removeCredential(): RedirectResponse
    {
        auth()->user()->openaiCredential?->delete();

        return redirect()->route('chat.index')->with('success', 'OpenAI credential removed.');
    }
}
