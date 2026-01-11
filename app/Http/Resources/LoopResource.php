<?php

namespace App\Http\Resources;

use App\Models\Loop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Loop
 */
class LoopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = $this->brand->timezone ?? 'America/New_York';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'platforms' => $this->platforms,
            'current_position' => $this->current_position,
            'total_cycles_completed' => $this->total_cycles_completed,
            'last_posted_at' => $this->last_posted_at?->setTimezone($timezone)->format('M d, Y g:i A'),
            'items_count' => $this->whenCounted('items'),
            'items' => $this->whenLoaded('items', fn () => LoopItemResource::collection($this->items)->resolve()),
            'schedules' => $this->whenLoaded('schedules', fn () => LoopScheduleResource::collection($this->schedules)->resolve()),
            'created_at' => $this->created_at?->format('M d, Y'),
            'updated_at' => $this->updated_at?->format('M d, Y'),
        ];
    }
}
