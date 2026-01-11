<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Media
 */
class MediaResource extends JsonResource
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
            'filename' => $this->filename,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'permanent_url' => route('media.view', $this->resource),
            'permanent_thumbnail_url' => route('media.thumbnail', $this->resource),
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'width' => $this->width,
            'height' => $this->height,
            'dimensions' => $this->dimensions,
            'alt_text' => $this->alt_text,
            'folder_id' => $this->folder_id,
            'folder' => new MediaFolderResource($this->whenLoaded('folder')),
            'created_at' => $this->created_at?->format('M d, Y'),
            'created_at_full' => $this->created_at?->format('M d, Y g:i A'),
        ];
    }
}
