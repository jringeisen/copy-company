<?php

namespace App\Http\Requests\SocialPost;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('socialPost'));
    }

    public function rules(): array
    {
        $brandId = $this->user()->currentBrand()?->id;

        return [
            'content' => ['required', 'string'],
            'hashtags' => ['nullable', 'array'],
            'link' => ['nullable', 'string', 'url'],
            'format' => ['nullable', 'string', 'in:feed,story,reel,carousel,pin,thread'],
            'media' => ['nullable', 'array'],
            'media.*.id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($brandId): void {
                    if (! Media::where('id', $value)->where('brand_id', $brandId)->exists()) {
                        $fail('One or more selected images do not exist.');
                    }
                },
            ],
        ];
    }
}
