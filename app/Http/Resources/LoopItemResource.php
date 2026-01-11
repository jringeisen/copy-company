<?php

namespace App\Http\Resources;

use App\Models\LoopItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoopItem
 */
class LoopItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = $this->loop->brand->timezone ?? 'America/New_York';

        return [
            'id' => $this->id,
            'position' => $this->position,
            'content' => $this->getPostContent(),
            'platform' => $this->getPostPlatform()?->value,
            'format' => $this->getPostFormat()->value,
            'hashtags' => $this->getPostHashtags(),
            'link' => $this->getPostLink(),
            'media' => $this->getPostMedia(),
            'times_posted' => $this->times_posted,
            'last_posted_at' => $this->last_posted_at?->setTimezone($timezone)->format('M d, Y g:i A'),
            'social_post' => new SocialPostResource($this->whenLoaded('socialPost')),
            'is_linked' => $this->isLinked(),
        ];
    }
}
