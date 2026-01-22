<?php

namespace App\Mcp\Tools\ContentSprints;

use App\Mcp\Concerns\RequiresBrand;
use App\Models\ContentSprint;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsIdempotent]
#[IsReadOnly]
class GetSprintTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'Get detailed information about a content sprint including all generated ideas.';

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint_id' => $schema->integer()
                ->description('The ID of the content sprint to retrieve.')
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
            'sprint_id' => ['required', 'integer'],
        ], [
            'sprint_id.required' => 'Please provide the sprint_id of the content sprint you want to retrieve.',
        ]);

        /** @var ContentSprint|null $sprint */
        $sprint = $brand->contentSprints()->find($validated['sprint_id']);

        if (! $sprint) {
            return Response::error('Content sprint not found. It may not exist or belong to your current brand.');
        }

        $ideas = collect($sprint->generated_content ?? [])->map(function ($idea, $index) use ($sprint) {
            return [
                'index' => $index,
                'title' => $idea['title'] ?? 'Untitled',
                'description' => $idea['description'] ?? null,
                'key_points' => $idea['key_points'] ?? [],
                'estimated_words' => $idea['estimated_words'] ?? null,
                'is_converted' => $sprint->isIdeaConverted($index),
            ];
        })->toArray();

        return Response::text(json_encode([
            'id' => $sprint->id,
            'title' => $sprint->title,
            'status' => $sprint->status->value,
            'inputs' => [
                'topics' => $sprint->inputs['topics'] ?? [],
                'goals' => $sprint->inputs['goals'] ?? null,
                'content_count' => $sprint->inputs['content_count'] ?? null,
            ],
            'ideas' => $ideas,
            'ideas_count' => count($ideas),
            'unconverted_ideas_count' => $sprint->unconverted_ideas_count,
            'converted_indices' => $sprint->converted_indices ?? [],
            'created_at' => $sprint->created_at->toIso8601String(),
            'completed_at' => $sprint->completed_at?->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }
}
