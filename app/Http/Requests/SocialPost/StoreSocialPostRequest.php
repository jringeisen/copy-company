<?php

namespace App\Http\Requests\SocialPost;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\SocialPost::class);
    }

    public function rules(): array
    {
        return [
            'post_id' => ['nullable', 'exists:posts,id'],
            'platform' => ['required', 'string', 'in:instagram,facebook,pinterest,linkedin,tiktok,twitter'],
            'format' => ['nullable', 'string', 'in:feed,story,reel,carousel,pin,thread'],
            'content' => ['required', 'string'],
            'hashtags' => ['nullable', 'array'],
            'link' => ['nullable', 'string', 'url'],
            'status' => ['nullable', 'string', 'in:draft,queued'],
            'ai_generated' => ['nullable', 'boolean'],
        ];
    }
}
