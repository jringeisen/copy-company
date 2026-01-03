<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class AtomizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $brand = $this->user()->currentBrand();
        if (! $brand) {
            return false;
        }

        // Verify the post belongs to the user's brand
        $post = \App\Models\Post::find($this->post_id);

        return $post && $post->brand_id === $brand->id;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'exists:posts,id'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:instagram,twitter,facebook,linkedin,pinterest,tiktok'],
        ];
    }

    public function messages(): array
    {
        return [
            'platforms.required' => 'Please select at least one platform.',
            'platforms.min' => 'Please select at least one platform.',
        ];
    }
}
