<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutoReplyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'preferred_conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'ai_additional_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
