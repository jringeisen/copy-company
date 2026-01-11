<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('brand'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('brands')->ignore($this->route('brand')->id), 'regex:/^[a-z0-9-]+$/'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'industry' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'voice_settings' => ['nullable', 'array'],
            'voice_settings.tone' => ['nullable', 'string', 'in:professional,casual,friendly,formal,persuasive'],
            'voice_settings.style' => ['nullable', 'string', 'in:conversational,academic,storytelling,instructional'],
            'voice_settings.sample_texts' => ['nullable', 'array', 'max:3'],
            'voice_settings.sample_texts.*' => ['string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'This slug is already taken.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'primary_color.regex' => 'Primary color must be a valid hex color (e.g., #6366f1).',
            'secondary_color.regex' => 'Secondary color must be a valid hex color (e.g., #6366f1).',
        ];
    }
}
