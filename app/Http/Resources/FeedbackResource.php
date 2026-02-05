<?php

namespace App\Http\Resources;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Feedback
 */
class FeedbackResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_icon' => $this->type->icon(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'priority_color' => $this->priority->color(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'description' => $this->description,
            'page_url' => $this->page_url,
            'user_agent' => $this->user_agent,
            'screenshot_url' => $this->getScreenshotUrl(),
            'admin_notes' => $this->admin_notes,
            'resolved_at' => $this->resolved_at?->format('M d, Y g:i A'),
            'created_at' => $this->created_at?->format('M d, Y g:i A'),
            'created_at_relative' => $this->created_at?->diffForHumans(),
            'is_open' => $this->isOpen(),
            'is_closed' => $this->isClosed(),
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'user_email' => $this->whenLoaded('user', fn () => $this->user?->email),
            'brand_name' => $this->whenLoaded('brand', fn () => $this->brand?->name),
            'brand_slug' => $this->whenLoaded('brand', fn () => $this->brand?->slug),
        ];
    }
}
