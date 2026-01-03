<?php

namespace App\Http\Requests\ContentSprint;

use Illuminate\Foundation\Http\FormRequest;

class AcceptContentSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contentSprint'));
    }

    public function rules(): array
    {
        return [
            'idea_indices' => ['required', 'array', 'min:1'],
            'idea_indices.*' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'idea_indices.required' => 'Please select at least one idea to accept.',
            'idea_indices.min' => 'Please select at least one idea to accept.',
        ];
    }
}
