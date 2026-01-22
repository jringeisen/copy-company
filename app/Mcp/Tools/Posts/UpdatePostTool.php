<?php

namespace App\Mcp\Tools\Posts;

use App\Mcp\Concerns\RequiresBrand;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdatePostTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'Update an existing post. Only provide the fields you want to change.';

    public function __construct(
        protected PostService $postService
    ) {}

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()
                ->description('The ID of the post to update.')
                ->required(),

            'title' => $schema->string()
                ->description('The title of the post.'),

            'content' => $schema->object()
                ->description('The post content in TipTap JSON format.'),

            'content_html' => $schema->string()
                ->description('Pre-rendered HTML content.'),

            'excerpt' => $schema->string()
                ->description('A brief summary or excerpt of the post.'),

            'tags' => $schema->array()
                ->description('Array of tags for the post.')
                ->items($schema->string()),

            'seo_title' => $schema->string()
                ->description('SEO-optimized title for search engines.'),

            'seo_description' => $schema->string()
                ->description('SEO meta description for search engines.'),

            'publish_to_blog' => $schema->boolean()
                ->description('Whether to publish to the public blog.'),

            'send_as_newsletter' => $schema->boolean()
                ->description('Whether to send as a newsletter when published.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $brand = $this->getBrand($request);

        if ($brand instanceof Response) {
            return $brand;
        }

        $validated = $request->validate([
            'post_id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'array'],
            'content_html' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'publish_to_blog' => ['nullable', 'boolean'],
            'send_as_newsletter' => ['nullable', 'boolean'],
        ], [
            'post_id.required' => 'Please provide the post_id of the post you want to update.',
        ]);

        /** @var Post|null $post */
        $post = $brand->posts()->find($validated['post_id']);

        if (! $post) {
            return Response::error('Post not found. It may not exist or belong to your current brand.');
        }

        if (Gate::denies('update', $post)) {
            return Response::error('You do not have permission to update this post.');
        }

        // Only update fields that were provided
        $updateData = array_filter([
            'title' => $validated['title'] ?? $post->title,
            'content' => $validated['content'] ?? $post->content,
            'content_html' => $validated['content_html'] ?? $post->content_html,
            'excerpt' => $validated['excerpt'] ?? $post->excerpt,
            'tags' => $validated['tags'] ?? $post->tags,
            'seo_title' => $validated['seo_title'] ?? $post->seo_title,
            'seo_description' => $validated['seo_description'] ?? $post->seo_description,
            'publish_to_blog' => $validated['publish_to_blog'] ?? $post->publish_to_blog,
            'send_as_newsletter' => $validated['send_as_newsletter'] ?? $post->send_as_newsletter,
        ], fn ($value) => $value !== null);

        $post = $this->postService->update($post, $updateData);

        return Response::text(json_encode([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status->value,
            'updated_at' => $post->updated_at->toIso8601String(),
            'message' => 'Post updated successfully.',
        ], JSON_PRETTY_PRINT));
    }
}
