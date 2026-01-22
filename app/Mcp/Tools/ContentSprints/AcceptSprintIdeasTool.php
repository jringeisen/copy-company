<?php

namespace App\Mcp\Tools\ContentSprints;

use App\Enums\ContentSprintStatus;
use App\Mcp\Concerns\RequiresBrand;
use App\Models\ContentSprint;
use App\Models\Post;
use App\Models\User;
use App\Services\ContentSprintService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AcceptSprintIdeasTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'Convert selected ideas from a content sprint into draft posts.';

    public function __construct(
        protected ContentSprintService $sprintService
    ) {}

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint_id' => $schema->integer()
                ->description('The ID of the content sprint.')
                ->required(),

            'idea_indices' => $schema->array()
                ->description('Array of idea indices (0-based) to convert to draft posts.')
                ->items($schema->integer())
                ->required(),
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

        $validated = $request->validate([
            'sprint_id' => ['required', 'integer'],
            'idea_indices' => ['required', 'array', 'min:1'],
            'idea_indices.*' => ['integer', 'min:0'],
        ], [
            'sprint_id.required' => 'Please provide the sprint_id of the content sprint.',
            'idea_indices.required' => 'Please provide at least one idea index to accept.',
            'idea_indices.min' => 'Please provide at least one idea index.',
        ]);

        /** @var ContentSprint|null $sprint */
        $sprint = $brand->contentSprints()->find($validated['sprint_id']);

        if (! $sprint) {
            return Response::error('Content sprint not found. It may not exist or belong to your current brand.');
        }

        if ($sprint->status !== ContentSprintStatus::Completed) {
            return Response::error("Cannot accept ideas from a sprint that is {$sprint->status->value}. The sprint must be completed first.");
        }

        if (Gate::denies('create', Post::class)) {
            return Response::error('You do not have permission to create posts, or you have reached your subscription limit.');
        }

        $generatedContent = $sprint->generated_content ?? [];
        $invalidIndices = array_filter($validated['idea_indices'], fn ($index) => ! isset($generatedContent[$index]));

        if (! empty($invalidIndices)) {
            return Response::error('Invalid idea indices: '.implode(', ', $invalidIndices).'. Valid range is 0-'.(count($generatedContent) - 1));
        }

        // Filter out already converted ideas
        $alreadyConverted = array_filter($validated['idea_indices'], fn ($index) => $sprint->isIdeaConverted($index));
        $newIndices = array_diff($validated['idea_indices'], $alreadyConverted);

        if (empty($newIndices)) {
            return Response::error('All selected ideas have already been converted to posts.');
        }

        $posts = $this->sprintService->acceptIdeas($sprint, $brand, $user->id, $newIndices);

        $createdPosts = $posts->map(fn (Post $post) => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status->value,
        ])->toArray();

        $message = count($createdPosts).' draft post(s) created.';
        if (! empty($alreadyConverted)) {
            $message .= ' '.count($alreadyConverted).' idea(s) were already converted.';
        }

        return Response::text(json_encode([
            'posts_created' => $createdPosts,
            'count' => count($createdPosts),
            'skipped_already_converted' => count($alreadyConverted),
            'message' => $message,
        ], JSON_PRETTY_PRINT));
    }
}
