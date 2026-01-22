<?php

namespace App\Mcp\Tools\Posts;

use App\Mcp\Concerns\RequiresBrand;
use App\Models\Post;
use App\Services\PostService;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class PublishPostTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'Publish a post immediately or schedule it for later. Can optionally send as newsletter.';

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
                ->description('The ID of the post to publish.')
                ->required(),

            'scheduled_at' => $schema->string()
                ->description('ISO 8601 datetime to schedule the post. If not provided, publishes immediately.'),

            'publish_to_blog' => $schema->boolean()
                ->description('Whether to publish to the public blog. Defaults to true.')
                ->default(true),

            'send_as_newsletter' => $schema->boolean()
                ->description('Whether to send as a newsletter. Defaults to false.')
                ->default(false),

            'subject_line' => $schema->string()
                ->description('Email subject line for newsletter. Required if send_as_newsletter is true.'),

            'preview_text' => $schema->string()
                ->description('Preview text shown in email clients. Optional.'),
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
            'scheduled_at' => ['nullable', 'string', 'date'],
            'publish_to_blog' => ['nullable', 'boolean'],
            'send_as_newsletter' => ['nullable', 'boolean'],
            'subject_line' => ['required_if:send_as_newsletter,true', 'nullable', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:150'],
        ], [
            'post_id.required' => 'Please provide the post_id of the post you want to publish.',
            'subject_line.required_if' => 'A subject line is required when sending as a newsletter.',
        ]);

        /** @var Post|null $post */
        $post = $brand->posts()->find($validated['post_id']);

        if (! $post) {
            return Response::error('Post not found. It may not exist or belong to your current brand.');
        }

        if (Gate::denies('update', $post)) {
            return Response::error('You do not have permission to publish this post.');
        }

        if ($post->isPublished()) {
            return Response::error('This post is already published.');
        }

        $options = [
            'publish_to_blog' => $validated['publish_to_blog'] ?? true,
            'send_as_newsletter' => $validated['send_as_newsletter'] ?? false,
            'subject_line' => $validated['subject_line'] ?? $post->title,
            'preview_text' => $validated['preview_text'] ?? null,
        ];

        if (isset($validated['scheduled_at'])) {
            $scheduledAt = Carbon::parse($validated['scheduled_at']);

            if ($scheduledAt->isPast()) {
                return Response::error('The scheduled date must be in the future.');
            }

            $options['scheduled_at'] = $scheduledAt->toIso8601String();
            $this->postService->schedule($post, $options);

            return Response::text(json_encode([
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->fresh()->status->value,
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'message' => "Post scheduled for {$scheduledAt->format('M j, Y g:i A')}.",
            ], JSON_PRETTY_PRINT));
        }

        $this->postService->publish($post, $options);

        $message = 'Post published successfully.';
        if ($options['send_as_newsletter']) {
            $message .= ' Newsletter is being sent to subscribers.';
        }

        return Response::text(json_encode([
            'id' => $post->id,
            'title' => $post->title,
            'status' => $post->fresh()->status->value,
            'published_at' => $post->fresh()->published_at?->toIso8601String(),
            'url' => $post->url,
            'message' => $message,
        ], JSON_PRETTY_PRINT));
    }
}
