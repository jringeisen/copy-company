<?php

namespace App\Http\Requests\ContentSprint;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentBrand() !== null;
    }

    public function rules(): array
    {
        return [
            'topics' => ['required', 'array', 'min:1', 'max:10'],
            'topics.*' => ['required', 'string', 'max:100'],
            'goals' => ['nullable', 'string', 'max:500'],
            'content_count' => ['required', 'integer', 'min:5', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'topics.required' => 'Please provide at least one topic.',
            'topics.min' => 'Please provide at least one topic.',
            'topics.max' => 'You can provide a maximum of 10 topics.',
            'content_count.min' => 'Content count must be at least 5.',
            'content_count.max' => 'Content count cannot exceed 30.',
        ];
    }
}
