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
            'channel' => ['required', 'in:whatsapp,telegram'],
            'identifier' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'ai_additional_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
