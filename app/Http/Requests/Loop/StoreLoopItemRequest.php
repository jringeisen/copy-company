<?php

namespace App\Http\Requests\Loop;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoopItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('loop'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'social_post_id' => ['nullable', 'exists:social_posts,id'],
            'content' => ['required_without:social_post_id', 'nullable', 'string'],
            'platform' => ['nullable', 'string', 'in:instagram,facebook,pinterest,linkedin,tiktok'],
            'format' => ['nullable', 'string', 'in:feed,story,reel,carousel,pin,thread'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string'],
            'link' => ['nullable', 'string', 'url'],
            'media' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required_without' => 'Content is required when not linking to an existing social post.',
        ];
    }
}
