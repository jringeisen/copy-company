<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'content_html' => $this->content_html,
            'featured_image' => $this->featured_image,
            'status' => $this->status?->value,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'tags' => $this->tags,
            'publish_to_blog' => $this->publish_to_blog,
            'send_as_newsletter' => $this->send_as_newsletter,
            'generate_social' => $this->generate_social,
            'ai_assistance_percentage' => $this->ai_assistance_percentage,
            'view_count' => $this->view_count,
            'email_open_count' => $this->email_open_count,
            'email_click_count' => $this->email_click_count,
            'url' => $this->when($this->relationLoaded('brand'), fn () => $this->url),
            'published_at' => $this->published_at?->format('M d, Y g:i A'),
            'published_at_form' => $this->published_at?->format('Y-m-d\TH:i'),
            'scheduled_at' => $this->scheduled_at?->format('M d, Y g:i A'),
            'scheduled_at_form' => $this->scheduled_at?->format('Y-m-d\TH:i'),
            'created_at' => $this->created_at?->format('M d, Y g:i A'),
            'updated_at' => $this->updated_at?->format('M d, Y g:i A'),
            'brand' => new BrandResource($this->whenLoaded('brand')),
        ];
    }
}
