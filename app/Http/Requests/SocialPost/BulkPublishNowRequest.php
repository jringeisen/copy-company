<?php

namespace App\Http\Requests\SocialPost;

use App\Models\SocialPost;
use Illuminate\Foundation\Http\FormRequest;

class BulkPublishNowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $brand = $this->user()->currentBrand();

        if (! $brand) {
            return false;
        }

        // Check if user has social.manage permission
        if (! $this->user()->can('social.manage')) {
            return false;
        }

        // Verify all social posts belong to the user's brand
        $ids = $this->input('social_post_ids', []);

        if (empty($ids)) {
            return true; // Validation will catch empty array
        }

        $ownedCount = SocialPost::whereIn('id', $ids)
            ->where('brand_id', $brand->id)
            ->count();

        return $ownedCount === count($ids);
    }

    public function rules(): array
    {
        return [
            'social_post_ids' => ['required', 'array'],
            'social_post_ids.*' => ['exists:social_posts,id'],
            'interval_minutes' => ['required', 'integer', 'min:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'social_post_ids.required' => 'Please select at least one post to publish.',
            'interval_minutes.required' => 'Please enter an interval between posts.',
            'interval_minutes.min' => 'Interval must be at least 5 minutes to prevent rate limiting.',
        ];
    }
}
