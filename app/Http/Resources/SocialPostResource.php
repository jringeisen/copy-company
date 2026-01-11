<?php

namespace App\Http\Resources;

use App\Models\Media;
use App\Models\SocialPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SocialPost
 */
class SocialPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Use already-loaded brand relationship to avoid N+1 queries
        $timezone = $this->relationLoaded('brand')
            ? ($this->brand->timezone ?? 'America/New_York')
            : 'America/New_York';

        return [
            'id' => $this->id,
            'platform' => $this->platform->value ?? null,
            'platform_display' => $this->platform_display_name,
            'format' => $this->format->value ?? null,
            'content' => $this->content,
            'media' => $this->getHydratedMedia(),
            'hashtags' => $this->hashtags,
            'link' => $this->link,
            'status' => $this->status->value ?? null,
            'status_color' => $this->status_color,
            'ai_generated' => $this->ai_generated,
            'user_edited' => $this->user_edited,
            'character_limit' => $this->character_limit,
            'failure_reason' => $this->failure_reason,
            'external_id' => $this->external_id,
            'analytics' => $this->analytics,
            'scheduled_at' => $this->scheduled_at?->setTimezone($timezone)->format('M d, Y g:i A'),
            'scheduled_at_form' => $this->scheduled_at?->setTimezone($timezone)->format('Y-m-d\TH:i'),
            'published_at' => $this->published_at?->setTimezone($timezone)->format('M d, Y g:i A'),
            'created_at' => $this->created_at?->format('M d, Y g:i A'),
            'updated_at' => $this->updated_at?->format('M d, Y g:i A'),
            'post' => $this->when($this->relationLoaded('post') && $this->post, function () {
                /** @var \App\Models\Post $post */
                $post = $this->post;

                return [
                    'id' => $post->id,
                    'title' => $post->title,
                ];
            }),
            'brand' => new BrandResource($this->whenLoaded('brand')),
        ];
    }

    /**
     * Hydrate media IDs into full media objects with fresh signed URLs.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getHydratedMedia(): array
    {
        $mediaIds = $this->media ?? [];

        if (empty($mediaIds)) {
            return [];
        }

        // If media is already an array of objects (legacy data), return as-is
        if (isset($mediaIds[0]) && is_array($mediaIds[0])) {
            return $mediaIds;
        }

        // Fetch media records and transform with fresh URLs
        return Media::whereIn('id', $mediaIds)
            ->get()
            ->map(fn (Media $media) => (new MediaResource($media))->resolve())
            ->toArray();
    }
}
