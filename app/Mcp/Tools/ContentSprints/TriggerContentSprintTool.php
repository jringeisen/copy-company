<?php

namespace App\Mcp\Tools\ContentSprints;

use App\Enums\ContentSprintStatus;
use App\Jobs\GenerateContentSprint;
use App\Mcp\Concerns\RequiresBrand;
use App\Models\ContentSprint;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TriggerContentSprintTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'Start an AI-powered content sprint to generate content ideas based on topics and goals.';

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topics' => $schema->array()
                ->description('Array of topics to generate content ideas for. Provide 1-10 topics.')
                ->items($schema->string())
                ->required(),

            'goals' => $schema->string()
                ->description('Optional goals or context for the content generation. E.g., "increase newsletter signups" or "establish thought leadership".'),

            'content_count' => $schema->integer()
                ->description('Number of content ideas to generate. Defaults to 10. Range: 5-30.')
                ->default(10),
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

        if (Gate::denies('create', ContentSprint::class)) {
            return Response::error('You do not have permission to create content sprints, or you have reached your subscription limit.');
        }

        $validated = $request->validate([
            'topics' => ['required', 'array', 'min:1', 'max:10'],
            'topics.*' => ['string', 'max:100'],
            'goals' => ['nullable', 'string', 'max:500'],
            'content_count' => ['nullable', 'integer', 'min:5', 'max:30'],
        ], [
            'topics.required' => 'Please provide at least one topic for the content sprint.',
            'topics.min' => 'Please provide at least one topic.',
            'topics.max' => 'You can provide up to 10 topics maximum.',
        ]);

        $contentCount = $validated['content_count'] ?? 10;

        // Create a title from the first few topics
        $titleTopics = array_slice($validated['topics'], 0, 3);
        $title = implode(', ', $titleTopics);
        if (count($validated['topics']) > 3) {
            $title .= ' +'.(count($validated['topics']) - 3).' more';
        }

        /** @var ContentSprint $sprint */
        $sprint = $brand->contentSprints()->create([
            'user_id' => $user->id,
            'title' => $title,
            'inputs' => [
                'topics' => $validated['topics'],
                'goals' => $validated['goals'] ?? '',
                'content_count' => $contentCount,
            ],
            'status' => ContentSprintStatus::Pending,
        ]);

        GenerateContentSprint::dispatch($sprint);

        return Response::text(json_encode([
            'sprint_id' => $sprint->id,
            'title' => $sprint->title,
            'status' => $sprint->status->value,
            'topics' => $validated['topics'],
            'content_count' => $contentCount,
            'message' => 'Content sprint started. Use GetSprintTool to check the status and retrieve generated ideas.',
        ], JSON_PRETTY_PRINT));
    }
}
