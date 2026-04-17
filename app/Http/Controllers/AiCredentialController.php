<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAiProviderSettingsRequest;
use App\Http\Requests\SaveAnthropicApiKeyRequest;
use App\Http\Requests\SaveAnthropicModelSettingsRequest;
use App\Http\Requests\SaveOpenAiApiKeyRequest;
use App\Http\Requests\SaveOpenAiModelSettingsRequest;
use App\Http\Requests\SaveVoyageApiKeyRequest;
use App\Http\Requests\SaveVoyageModelSettingsRequest;
use App\Models\UserAiCredential;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class AiCredentialController extends Controller
{
    public function redirect(): RedirectResponse
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
            ]
        );

        return redirect()->route('ai.index')->with('success', 'OpenAI account connected.');
    }

    public function saveApiKey(SaveOpenAiApiKeyRequest $request): RedirectResponse
    {
        $credential = auth()->user()->aiCredentialFor('openai') ?? new UserAiCredential([
            'user_id' => auth()->id(),
            'provider' => 'openai',
        ]);

        $credential->fill([
            'auth_method' => 'api_key',
            'api_key' => $request->validated('api_key'),
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'external_user_id' => null,
            'external_email' => null,
        ])->save();

        return redirect()->route('ai.index')->with('success', 'OpenAI API key saved.');
    }

    public function removeCredential(): RedirectResponse
    {
        auth()->user()->aiCredentialFor('openai')?->delete();

        return redirect()->route('ai.index')->with('success', 'OpenAI credential removed.');
    }

    public function saveAnthropicApiKey(SaveAnthropicApiKeyRequest $request): RedirectResponse
    {
        $credential = auth()->user()->aiCredentialFor('anthropic') ?? new UserAiCredential([
            'user_id' => auth()->id(),
            'provider' => 'anthropic',
        ]);

        $credential->fill([
            'auth_method' => 'api_key',
            'api_key' => $request->validated('anthropic_api_key'),
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'external_user_id' => null,
            'external_email' => null,
        ])->save();

        return redirect()->route('ai.index')->with('success', 'Anthropic API key saved.');
    }

    public function removeAnthropicCredential(): RedirectResponse
    {
        auth()->user()->aiCredentialFor('anthropic')?->delete();

        return redirect()->route('ai.index')->with('success', 'Anthropic credential removed.');
    }

    public function saveVoyageApiKey(SaveVoyageApiKeyRequest $request): RedirectResponse
    {
        $credential = auth()->user()->aiCredentialFor('voyage') ?? new UserAiCredential([
            'user_id' => auth()->id(),
            'provider' => 'voyage',
        ]);

        $credential->fill([
            'auth_method' => 'api_key',
            'api_key' => $request->validated('voyage_api_key'),
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'external_user_id' => null,
            'external_email' => null,
        ])->save();

        return redirect()->route('ai.index')->with('success', 'Voyage API key saved.');
    }

    public function saveOpenAiModels(SaveOpenAiModelSettingsRequest $request): RedirectResponse
    {
        $this->saveProviderMetadata('openai', [
            'chat_model' => $request->validated('chat_model'),
            'embedding_model' => $request->validated('embedding_model'),
        ]);

        return redirect()->route('ai.index')->with('success', 'OpenAI model settings saved.');
    }

    public function saveAnthropicModels(SaveAnthropicModelSettingsRequest $request): RedirectResponse
    {
        $this->saveProviderMetadata('anthropic', [
            'chat_model' => $request->validated('chat_model'),
        ]);

        return redirect()->route('ai.index')->with('success', 'Anthropic model settings saved.');
    }

    public function saveVoyageModels(SaveVoyageModelSettingsRequest $request): RedirectResponse
    {
        $this->saveProviderMetadata('voyage', [
            'embedding_model' => $request->validated('embedding_model'),
        ]);

        return redirect()->route('ai.index')->with('success', 'Voyage model settings saved.');
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

    protected function saveProviderMetadata(string $provider, array $attributes): void
    {
        $credential = auth()->user()->aiCredentialFor($provider) ?? new UserAiCredential([
            'user_id' => auth()->id(),
            'provider' => $provider,
        ]);

        $metadata = $credential->metadata ?? [];

        foreach ($attributes as $key => $value) {
            if (blank($value)) {
                unset($metadata[$key]);
            } else {
                $metadata[$key] = $value;
            }
        }

        $credential->metadata = $metadata;
        $credential->save();
    }
}
