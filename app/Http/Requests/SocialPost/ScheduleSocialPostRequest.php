<?php

namespace App\Http\Requests\SocialPost;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('socialPost'));
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'The scheduled date must be in the future.',
        ];
    }
}
