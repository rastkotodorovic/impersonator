<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:10000'],
            'history' => ['array'],
            'history.*.role' => ['required', 'string', 'in:user,assistant,system'],
            'history.*.content' => ['required', 'string'],
            'model' => ['nullable', 'string'],
        ];
    }
}
