<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class PublishPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        return [
            'publish_to_blog' => ['boolean'],
            'send_as_newsletter' => ['boolean'],
            'subject_line' => ['required_if:send_as_newsletter,true', 'nullable', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'schedule_mode' => ['required', 'string', 'in:now,scheduled'],
            'scheduled_at' => ['required_if:schedule_mode,scheduled', 'nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_line.required_if' => 'A subject line is required when sending as newsletter.',
            'scheduled_at.required_if' => 'A schedule date is required when scheduling for later.',
            'scheduled_at.after' => 'The scheduled date must be in the future.',
        ];
    }
}
