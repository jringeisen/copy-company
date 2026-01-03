<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeletePostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:posts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Please select at least one post to delete.',
            'ids.min' => 'Please select at least one post to delete.',
        ];
    }
}
