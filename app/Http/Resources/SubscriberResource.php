<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberResource extends JsonResource
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
            'email' => $this->email,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'status' => $this->status?->value,
            'source' => $this->source,
            'confirmed_at' => $this->confirmed_at?->format('M d, Y g:i A'),
            'subscribed_at' => $this->subscribed_at?->format('M d, Y g:i A'),
            'unsubscribed_at' => $this->unsubscribed_at?->format('M d, Y g:i A'),
            'created_at' => $this->created_at?->format('M d, Y g:i A'),
            'updated_at' => $this->updated_at?->format('M d, Y g:i A'),
            'brand' => new BrandResource($this->whenLoaded('brand')),
        ];
    }
}
