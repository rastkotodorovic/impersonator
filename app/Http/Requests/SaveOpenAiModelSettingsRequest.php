<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveOpenAiModelSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chat_model' => ['nullable', 'string', 'max:120'],
            'embedding_model' => ['nullable', 'string', 'max:120'],
        ];
    }
}
