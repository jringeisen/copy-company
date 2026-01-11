<?php

namespace App\Http\Requests\Loop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoopItemRequest extends FormRequest
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
            'content' => ['required', 'string'],
            'format' => ['nullable', 'string', 'in:feed,story,reel,carousel,pin,thread'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string'],
            'link' => ['nullable', 'string', 'url'],
            'media' => ['nullable', 'array'],
        ];
    }
}
