<?php

namespace App\Mcp\Tools\Posts;

use App\Mcp\Concerns\RequiresBrand;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreatePostTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'Create a new draft post for the current brand.';

    public function __construct(
        protected PostService $postService
    ) {}

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('The title of the post.')
                ->required(),

            'content' => $schema->object()
                ->description('The post content in TipTap JSON format. Optional for initial creation.'),

            'content_html' => $schema->string()
                ->description('Pre-rendered HTML content. Optional.'),

            'excerpt' => $schema->string()
                ->description('A brief summary or excerpt of the post.'),

            'tags' => $schema->array()
                ->description('Array of tags for the post.')
                ->items($schema->string()),

            'seo_title' => $schema->string()
                ->description('SEO-optimized title for search engines.'),

            'seo_description' => $schema->string()
                ->description('SEO meta description for search engines.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $brand = $this->getBrand($request);

        if ($brand instanceof Response) {
            return $brand;
        }

        /** @var User $user */
        $user = $request->user();

        if (Gate::denies('create', \App\Models\Post::class)) {
            return Response::error('You do not have permission to create posts, or you have reached your subscription limit.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'array'],
            'content_html' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
        ], [
            'title.required' => 'Please provide a title for the post.',
            'title.max' => 'The title cannot exceed 255 characters.',
        ]);

        // Provide default empty TipTap document if content not provided
        if (! isset($validated['content'])) {
            $validated['content'] = ['type' => 'doc', 'content' => []];
        }

        $post = $this->postService->create($brand, $user->id, $validated);

        return Response::text(json_encode([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status->value,
            'created_at' => $post->created_at->toIso8601String(),
            'message' => 'Post created successfully as a draft.',
        ], JSON_PRETTY_PRINT));
    }
}
