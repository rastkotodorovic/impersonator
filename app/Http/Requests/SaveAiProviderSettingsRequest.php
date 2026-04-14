<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveAiProviderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chat_provider' => ['required', 'in:openai,anthropic'],
            'embedding_provider' => ['required', 'in:openai,voyage'],
        ];
    }
}
