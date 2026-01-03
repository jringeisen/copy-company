<?php

namespace App\Http\Requests\SocialPost;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('socialPost'));
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'hashtags' => ['nullable', 'array'],
            'link' => ['nullable', 'string', 'url'],
            'format' => ['nullable', 'string', 'in:feed,story,reel,carousel,pin,thread'],
        ];
    }
}
