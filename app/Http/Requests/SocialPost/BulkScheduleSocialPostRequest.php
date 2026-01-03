<?php

namespace App\Http\Requests\SocialPost;

use Illuminate\Foundation\Http\FormRequest;

class BulkScheduleSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentBrand() !== null;
    }

    public function rules(): array
    {
        return [
            'social_post_ids' => ['required', 'array'],
            'social_post_ids.*' => ['exists:social_posts,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'interval_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'social_post_ids.required' => 'Please select at least one post to schedule.',
            'scheduled_at.after' => 'The scheduled date must be in the future.',
        ];
    }
}
