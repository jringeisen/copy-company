<?php

namespace App\Http\Controllers;

use App\Enums\ContentSprintStatus;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\ContentSprint\AcceptContentSprintRequest;
use App\Http\Requests\ContentSprint\StoreContentSprintRequest;
use App\Jobs\GenerateContentSprint;
use App\Models\ContentSprint;
use App\Services\ContentSprintService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContentSprintController extends Controller
{
    use HasBrandAuthorization;

    public function __construct(
        protected ContentSprintService $contentSprintService
    ) {}

    public function index(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $sprints = $brand->contentSprints()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ContentSprint $sprint): array => [
                'id' => $sprint->id,
                'title' => $sprint->title,
                'status' => $sprint->status->value,
                'status_color' => $sprint->status_color,
                'ideas_count' => $sprint->ideas_count,
                'topics' => $sprint->inputs['topics'] ?? [],
                'created_at' => $sprint->created_at->format('M d, Y'),
                'completed_at' => $sprint->completed_at?->format('M d, Y'),
            ]);

        return Inertia::render('ContentSprint/Index', [
            'sprints' => $sprints,
            'brand' => $brand,
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        return Inertia::render('ContentSprint/Create', [
            'brand' => $brand,
        ]);
    }

    public function store(StoreContentSprintRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();
        $validated = $request->validated();

        $sprint = $brand->contentSprints()->create([
            'user_id' => auth()->id(),
            'title' => 'Sprint: '.implode(', ', array_slice($validated['topics'], 0, 3)),
            'inputs' => [
                'topics' => $validated['topics'],
                'goals' => $validated['goals'] ?? '',
                'content_count' => $validated['content_count'],
            ],
            'status' => ContentSprintStatus::Pending,
        ]);

        GenerateContentSprint::dispatch($sprint);

        return redirect()->route('content-sprints.show', $sprint);
    }

    public function show(ContentSprint $contentSprint): Response|RedirectResponse
    {
        $this->authorize('view', $contentSprint);

        $brand = $this->currentBrand();

        return Inertia::render('ContentSprint/Show', [
            'sprint' => [
                'id' => $contentSprint->id,
                'title' => $contentSprint->title,
                'status' => $contentSprint->status->value,
                'status_color' => $contentSprint->status_color,
                'inputs' => $contentSprint->inputs,
                'generated_content' => $contentSprint->generated_content,
                'converted_indices' => $contentSprint->converted_indices ?? [],
                'ideas_count' => $contentSprint->ideas_count,
                'unconverted_ideas_count' => $contentSprint->unconverted_ideas_count,
                'created_at' => $contentSprint->created_at->format('M d, Y \a\t g:i A'),
                'completed_at' => $contentSprint->completed_at?->format('M d, Y \a\t g:i A'),
            ],
            'brand' => $brand,
        ]);
    }

    public function accept(AcceptContentSprintRequest $request, ContentSprint $contentSprint): RedirectResponse
    {
        if ($contentSprint->status !== ContentSprintStatus::Completed) {
            return back()->with('error', 'Sprint must be completed before accepting ideas.');
        }

        $validated = $request->validated();
        $brand = $this->currentBrand();

        $posts = $this->contentSprintService->acceptIdeas(
            $contentSprint,
            $brand,
            auth()->id(),
            $validated['idea_indices']
        );

        $postsCreated = $posts->count();

        return redirect()->route('posts.index')
            ->with('success', "{$postsCreated} draft posts created from sprint!");
    }

    public function retry(ContentSprint $contentSprint): RedirectResponse
    {
        $this->authorize('update', $contentSprint);

        if ($contentSprint->status !== ContentSprintStatus::Failed) {
            return back()->with('error', 'Only failed sprints can be retried.');
        }

        $contentSprint->update(['status' => ContentSprintStatus::Pending]);
        GenerateContentSprint::dispatch($contentSprint);

        return redirect()->route('content-sprints.show', $contentSprint)
            ->with('success', 'Sprint generation restarted.');
    }
}
