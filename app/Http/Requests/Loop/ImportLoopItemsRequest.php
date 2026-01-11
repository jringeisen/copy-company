<?php

namespace App\Http\Requests\Loop;

use Illuminate\Foundation\Http\FormRequest;

class ImportLoopItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('loop'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a CSV file to import.',
            'file.mimes' => 'The file must be a CSV file.',
            'file.max' => 'The file cannot be larger than 10MB.',
        ];
    }
}
