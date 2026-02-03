<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
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
            'content' => $this->sanitizeTipTapContent($this->content),
            'content_html' => $this->content_html,
            'featured_image' => $this->featured_image,
            'status' => $this->status->value ?? null,
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

    /**
     * Remove invalid text nodes from TipTap JSON content.
     *
     * @param  array<string, mixed>|null  $content
     * @return array<string, mixed>|null
     */
    protected function sanitizeTipTapContent(?array $content): ?array
    {
        if ($content === null || ! isset($content['content'])) {
            return $content;
        }

        $content['content'] = $this->sanitizeNodes($content['content']);

        return $content;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    protected function sanitizeNodes(array $nodes): array
    {
        $cleaned = [];

        foreach ($nodes as $node) {
            if (($node['type'] ?? null) === 'text') {
                if (! isset($node['text']) || ! is_string($node['text']) || $node['text'] === '') {
                    continue;
                }
            }

            if (isset($node['content']) && is_array($node['content'])) {
                $node['content'] = $this->sanitizeNodes($node['content']);
            }

            $cleaned[] = $node;
        }

        return array_values($cleaned);
    }
}
