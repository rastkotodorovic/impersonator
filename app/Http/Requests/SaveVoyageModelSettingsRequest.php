<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveVoyageModelSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'embedding_model' => ['nullable', 'string', 'max:120'],
        ];
    }
}
