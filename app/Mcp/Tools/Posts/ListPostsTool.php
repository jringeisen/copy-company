<?php

namespace App\Mcp\Tools\Posts;

use App\Enums\PostStatus;
use App\Mcp\Concerns\RequiresBrand;
use App\Models\Brand;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsIdempotent]
#[IsReadOnly]
class ListPostsTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'List posts for the current brand with optional filtering by status.';

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['draft', 'scheduled', 'published', 'archived'])
                ->description('Filter posts by status. If not provided, returns all posts.'),

            'limit' => $schema->integer()
                ->description('Maximum number of posts to return. Defaults to 20, max 100.')
                ->default(20),
        ];
    }

    public function handle(Request $request): Response
    {
        $brand = $this->getBrand($request);

        if ($brand instanceof Response) {
            return $brand;
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:draft,scheduled,published,archived'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = $validated['limit'] ?? 20;

        /** @var Brand $brand */
        $query = $brand->posts()->orderBy('created_at', 'desc');

        if (isset($validated['status'])) {
            $status = PostStatus::from($validated['status']);
            $query->where('status', $status);
        }

        $posts = $query->limit($limit)->get()->map(fn ($post) => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'status' => $post->status->value,
            'published_at' => $post->published_at?->toIso8601String(),
            'scheduled_at' => $post->scheduled_at?->toIso8601String(),
            'created_at' => $post->created_at->toIso8601String(),
            'url' => $post->url,
        ]);

        return Response::text(json_encode([
            'posts' => $posts->toArray(),
            'count' => $posts->count(),
            'brand' => $brand->name,
        ], JSON_PRETTY_PRINT));
    }
}
