<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreFacebookImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archive' => ['nullable', 'file', 'mimes:zip,txt', 'max:5242880', 'required_without:source_path'],
            'source_path' => ['nullable', 'string', 'max:2000', 'required_without:archive'],
            'me_name' => ['required', 'string', 'max:255'],
            'replace_existing' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'archive.required_without' => 'Upload a Facebook, Instagram, or WhatsApp export file or provide a local export path.',
            'source_path.required_without' => 'Upload a Facebook, Instagram, or WhatsApp export file or provide a local export path.',
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        if ($this->bodyWasDropped()) {
            $fallbackValidator = Validator::make([], []);
            $fallbackValidator->errors()->add(
                'archive',
                'The upload did not reach Laravel. This usually happens with very large export files. Use the local export path field instead of browser upload for huge archives.'
            );

            throw (new ValidationException($fallbackValidator))
                ->errorBag($this->errorBag)
                ->redirectTo($this->getRedirectUrl());
        }

        parent::failedValidation($validator);
    }

    protected function bodyWasDropped(): bool
    {
        $contentLength = (int) $this->server('CONTENT_LENGTH', 0);

        return $contentLength > 0
            && ! $this->hasFile('archive')
            && blank($this->input('source_path'))
            && blank($this->input('me_name'));
    }
}
