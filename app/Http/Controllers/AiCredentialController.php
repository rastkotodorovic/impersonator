<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAiProviderSettingsRequest;
use App\Http\Requests\SaveAnthropicApiKeyRequest;
use App\Http\Requests\SaveOpenAiApiKeyRequest;
use App\Http\Requests\SaveVoyageApiKeyRequest;
use App\Models\UserAiCredential;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class AiCredentialController extends Controller
{
    public function redirect(): RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('openai')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $openaiUser = Socialite::driver('openai')->user();

        UserAiCredential::updateOrCreate(
            ['user_id' => auth()->id(), 'provider' => 'openai'],
            [
                'auth_method' => 'oauth',
                'api_key' => null,
                'access_token' => $openaiUser->token,
                'refresh_token' => $openaiUser->refreshToken,
                'token_expires_at' => $openaiUser->expiresIn
                    ? now()->addSeconds($openaiUser->expiresIn)
                    : null,
                'external_user_id' => $openaiUser->getId(),
                'external_email' => $openaiUser->getEmail(),
                'metadata' => null,
            ]
        );

        return redirect()->route('ai.index')->with('success', 'OpenAI account connected.');
    }

    public function saveApiKey(SaveOpenAiApiKeyRequest $request): RedirectResponse
    {
        UserAiCredential::updateOrCreate(
            ['user_id' => auth()->id(), 'provider' => 'openai'],
            [
                'auth_method' => 'api_key',
                'api_key' => $request->validated('api_key'),
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
                'external_user_id' => null,
                'external_email' => null,
                'metadata' => null,
            ]
        );

        return redirect()->route('ai.index')->with('success', 'OpenAI API key saved.');
    }

    public function removeCredential(): RedirectResponse
    {
        auth()->user()->aiCredentialFor('openai')?->delete();

        return redirect()->route('ai.index')->with('success', 'OpenAI credential removed.');
    }

    public function saveAnthropicApiKey(SaveAnthropicApiKeyRequest $request): RedirectResponse
    {
        UserAiCredential::updateOrCreate(
            ['user_id' => auth()->id(), 'provider' => 'anthropic'],
            [
                'auth_method' => 'api_key',
                'api_key' => $request->validated('anthropic_api_key'),
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
                'external_user_id' => null,
                'external_email' => null,
                'metadata' => null,
            ],
        );

        return redirect()->route('ai.index')->with('success', 'Anthropic API key saved.');
    }

    public function removeAnthropicCredential(): RedirectResponse
    {
        auth()->user()->aiCredentialFor('anthropic')?->delete();

        return redirect()->route('ai.index')->with('success', 'Anthropic credential removed.');
    }

    public function saveVoyageApiKey(SaveVoyageApiKeyRequest $request): RedirectResponse
    {
        UserAiCredential::updateOrCreate(
            ['user_id' => auth()->id(), 'provider' => 'voyage'],
            [
                'auth_method' => 'api_key',
                'api_key' => $request->validated('voyage_api_key'),
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
                'external_user_id' => null,
                'external_email' => null,
                'metadata' => null,
            ],
        );

        return redirect()->route('ai.index')->with('success', 'Voyage API key saved.');
    }

    public function removeVoyageCredential(): RedirectResponse
    {
        auth()->user()->aiCredentialFor('voyage')?->delete();

        return redirect()->route('ai.index')->with('success', 'Voyage credential removed.');
    }

    public function saveProviders(SaveAiProviderSettingsRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('ai.index')->with('success', 'AI provider preferences updated.');
    }
}
