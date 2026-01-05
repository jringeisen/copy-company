<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class InitiateDomainVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->currentBrand() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!:\/\/)([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/',
            ],
            'from_address' => [
                'nullable',
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
            'domain.regex' => 'Please enter a valid domain name (e.g., example.com).',
            'from_address.regex' => 'The from address can only contain letters, numbers, dots, underscores, percent signs, plus signs, and hyphens.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('domain')) {
            // Remove any protocol prefix
            $domain = preg_replace('#^https?://#', '', $this->domain);
            // Remove any trailing slash or path
            $domain = explode('/', $domain)[0];
            // Lowercase
            $domain = strtolower(trim($domain));

            $this->merge(['domain' => $domain]);
        }

        if ($this->has('from_address')) {
            $this->merge([
                'from_address' => strtolower(trim($this->from_address)),
            ]);
        }
    }
}
