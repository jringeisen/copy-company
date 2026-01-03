<?php

namespace App\Http\Requests\Subscriber;

use Illuminate\Foundation\Http\FormRequest;

class ImportSubscribersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentBrand() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to import.',
            'file.mimes' => 'The file must be a CSV file.',
            'file.max' => 'The file cannot be larger than 10MB.',
        ];
    }
}
