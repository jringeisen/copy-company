<?php

namespace App\Http\Resources;

use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MediaFolder
 */
class MediaFolderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'path' => $this->path,
            'parent_id' => $this->parent_id,
            'media_count' => $this->whenCounted('media'),
            'children' => MediaFolderResource::collection($this->whenLoaded('children')),
            'descendants' => MediaFolderResource::collection($this->whenLoaded('descendants')),
            'created_at' => $this->created_at?->format('M d, Y'),
        ];
    }
}
