<?php

namespace App\Mcp\Tools\Posts;

use App\Mcp\Concerns\RequiresBrand;
use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsIdempotent]
#[IsReadOnly]
class GetPostTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'Get detailed information about a specific post including its content.';

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()
                ->description('The ID of the post to retrieve.')
                ->required(),
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
        ], [
            'post_id.required' => 'Please provide the post_id of the post you want to retrieve.',
        ]);

        /** @var Post|null $post */
        $post = $brand->posts()->find($validated['post_id']);

        if (! $post) {
            return Response::error('Post not found. It may not exist or belong to your current brand.');
        }

        return Response::text(json_encode([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'content_html' => $post->content_html,
            'featured_image' => $post->featured_image,
            'seo_title' => $post->seo_title,
            'seo_description' => $post->seo_description,
            'tags' => $post->tags,
            'status' => $post->status->value,
            'publish_to_blog' => $post->publish_to_blog,
            'send_as_newsletter' => $post->send_as_newsletter,
            'published_at' => $post->published_at?->toIso8601String(),
            'scheduled_at' => $post->scheduled_at?->toIso8601String(),
            'created_at' => $post->created_at->toIso8601String(),
            'updated_at' => $post->updated_at->toIso8601String(),
            'url' => $post->url,
        ], JSON_PRETTY_PRINT));
    }
}
