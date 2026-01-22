<?php

namespace App\Mcp\Tools\ContentSprints;

use App\Enums\ContentSprintStatus;
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
class ListSprintsTool extends Tool
{
    use RequiresBrand;

    protected string $description = 'List content sprints for the current brand with optional filtering by status.';

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['pending', 'generating', 'completed', 'failed'])
                ->description('Filter sprints by status. If not provided, returns all sprints.'),

            'limit' => $schema->integer()
                ->description('Maximum number of sprints to return. Defaults to 10, max 50.')
                ->default(10),
        ];
    }

    public function handle(Request $request): Response
    {
        $brand = $this->getBrand($request);

        if ($brand instanceof Response) {
            return $brand;
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,generating,completed,failed'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = $validated['limit'] ?? 10;

        /** @var Brand $brand */
        $query = $brand->contentSprints()->orderBy('created_at', 'desc');

        if (isset($validated['status'])) {
            $status = ContentSprintStatus::from($validated['status']);
            $query->where('status', $status);
        }

        $sprints = $query->limit($limit)->get()->map(fn ($sprint) => [
            'id' => $sprint->id,
            'title' => $sprint->title,
            'status' => $sprint->status->value,
            'ideas_count' => $sprint->ideas_count,
            'unconverted_ideas_count' => $sprint->unconverted_ideas_count,
            'topics' => $sprint->inputs['topics'] ?? [],
            'created_at' => $sprint->created_at->toIso8601String(),
            'completed_at' => $sprint->completed_at?->toIso8601String(),
        ]);

        return Response::text(json_encode([
            'sprints' => $sprints->toArray(),
            'count' => $sprints->count(),
            'brand' => $brand->name,
        ], JSON_PRETTY_PRINT));
    }
}
