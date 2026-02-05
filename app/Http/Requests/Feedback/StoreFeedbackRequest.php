<?php

namespace App\Http\Requests\Feedback;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Feedback::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(FeedbackType::class)],
            'priority' => ['required', new Enum(FeedbackPriority::class)],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'page_url' => ['required', 'string', 'max:500'],
            'user_agent' => ['nullable', 'string', 'max:500'],
            'screenshot' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please select a feedback type.',
            'priority.required' => 'Please select a priority level.',
            'description.required' => 'Please provide a description of your feedback.',
            'description.min' => 'Description must be at least 10 characters.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'screenshot.image' => 'Screenshot must be an image file.',
            'screenshot.max' => 'Screenshot cannot exceed 5MB.',
        ];
    }
}
