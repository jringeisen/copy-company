<?php

namespace App\Services;

use App\Enums\NewsletterSendStatus;
use App\Enums\PostStatus;
use App\Jobs\ProcessNewsletterSend;
use App\Models\Brand;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class PostService
{
    /**
     * Sanitize HTML content to prevent XSS attacks.
     */
    protected function sanitizeHtml(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return Purifier::clean($html, 'blog');
    }

    /**
     * Sanitize TipTap JSON content by removing invalid text nodes.
     *
     * Text nodes with null or empty text values cause TipTap to throw
     * "Invalid text node in JSON" errors when loading content.
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
     * Recursively sanitize an array of TipTap nodes.
     *
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

    /**
     * Create a new post from validated data.
     *
     * @param  array<string, mixed>  $validated
     */
    public function create(Brand $brand, int $userId, array $validated): Post
    {
        /** @var Post */
        return $brand->posts()->create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'content' => $this->sanitizeTipTapContent($validated['content']),
            'content_html' => $this->sanitizeHtml($validated['content_html'] ?? null),
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image' => $validated['featured_image'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'publish_to_blog' => $validated['publish_to_blog'] ?? true,
            'send_as_newsletter' => $validated['send_as_newsletter'] ?? true,
            'generate_social' => $validated['generate_social'] ?? true,
            'status' => PostStatus::Draft,
        ]);
    }

    /**
     * Update a post from validated data.
     *
     * @param  array<string, mixed>  $validated
     */
    public function update(Post $post, array $validated): Post
    {
        $post->update([
            'title' => $validated['title'],
            'content' => $this->sanitizeTipTapContent($validated['content']),
            'content_html' => $this->sanitizeHtml($validated['content_html'] ?? null),
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image' => $validated['featured_image'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'publish_to_blog' => $validated['publish_to_blog'] ?? true,
            'send_as_newsletter' => $validated['send_as_newsletter'] ?? true,
            'generate_social' => $validated['generate_social'] ?? true,
        ]);

        return $post;
    }

    /**
     * Publish a post immediately.
     *
     * @param  array<string, mixed>  $options
     */
    public function publish(Post $post, array $options): void
    {
        DB::transaction(function () use ($post, $options) {
            $post->update([
                'status' => PostStatus::Published,
                'published_at' => now(),
                'publish_to_blog' => $options['publish_to_blog'] ?? true,
                'send_as_newsletter' => $options['send_as_newsletter'] ?? false,
            ]);

            if ($options['send_as_newsletter'] ?? false) {
                $this->createAndDispatchNewsletter($post, $options, now());
            }
        });
    }

    /**
     * Schedule a post for future publishing.
     *
     * @param  array<string, mixed>  $options
     */
    public function schedule(Post $post, array $options): void
    {
        DB::transaction(function () use ($post, $options) {
            $scheduledAt = Carbon::parse($options['scheduled_at']);

            $post->update([
                'status' => PostStatus::Scheduled,
                'scheduled_at' => $scheduledAt,
                'publish_to_blog' => $options['publish_to_blog'] ?? true,
                'send_as_newsletter' => $options['send_as_newsletter'] ?? false,
            ]);

            if ($options['send_as_newsletter'] ?? false) {
                $this->createNewsletterSend($post, $options, $scheduledAt);
            }
        });
    }

    /**
     * Create a newsletter send record and dispatch the processing job.
     *
     * @param  array<string, mixed>  $options
     */
    protected function createAndDispatchNewsletter(Post $post, array $options, Carbon $scheduledAt): void
    {
        $newsletterSend = $this->createNewsletterSend($post, $options, $scheduledAt);
        ProcessNewsletterSend::dispatch($newsletterSend);
    }

    /**
     * Create a newsletter send record.
     *
     * @param  array<string, mixed>  $options
     */
    protected function createNewsletterSend(Post $post, array $options, Carbon $scheduledAt): \App\Models\NewsletterSend
    {
        /** @var Brand $brand */
        $brand = $post->brand;

        /** @var \App\Models\NewsletterSend */
        return $post->newsletterSend()->create([
            'brand_id' => $post->brand_id,
            'subject_line' => $options['subject_line'],
            'preview_text' => $options['preview_text'] ?? null,
            'provider' => $brand->newsletter_provider,
            'status' => NewsletterSendStatus::Scheduled,
            'scheduled_at' => $scheduledAt,
        ]);
    }
}
