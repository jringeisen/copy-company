<?php

namespace App\Http\Requests\Post;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeletePostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $brand = $this->user()->currentBrand();

        if (! $brand) {
            return false;
        }

        // Verify all posts belong to the user's brand
        $ids = $this->input('ids', []);

        if (empty($ids)) {
            return true; // Validation will catch empty array
        }

        $ownedCount = Post::whereIn('id', $ids)
            ->where('brand_id', $brand->id)
            ->count();

        return $ownedCount === count($ids);
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
