<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailFromRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $brand = auth()->user()?->currentBrand();

        return auth()->check()
            && $brand !== null
            && $brand->custom_email_domain !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_address' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-zA-Z0-9._%+-]+$/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from_address.regex' => 'The from address can only contain letters, numbers, dots, underscores, percent signs, plus signs, and hyphens.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('from_address')) {
            $this->merge([
                'from_address' => strtolower(trim($this->from_address)),
            ]);
        }
    }
}
