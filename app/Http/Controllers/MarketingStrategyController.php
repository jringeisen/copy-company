<?php

namespace App\Http\Controllers;

use App\Enums\MarketingStrategyStatus;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\Strategy\ConvertBlogPostRequest;
use App\Http\Requests\Strategy\ConvertLoopRequest;
use App\Http\Requests\Strategy\ConvertSocialPostRequest;
use App\Jobs\GenerateMarketingStrategy;
use App\Models\MarketingStrategy;
use App\Services\MarketingStrategyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MarketingStrategyController extends Controller
{
    use HasBrandAuthorization;

    public function __construct(
        protected MarketingStrategyService $strategyService
    ) {}

    public function index(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $strategies = $brand->marketingStrategies()
            ->orderByDesc('week_start')
            ->get()
            ->map(fn (MarketingStrategy $strategy): array => [
                'id' => $strategy->id,
                'week_start' => $strategy->week_start->format('M d, Y'),
                'week_end' => $strategy->week_end->format('M d, Y'),
                'week_start_raw' => $strategy->week_start->toDateString(),
                'status' => $strategy->status->value,
                'status_color' => $strategy->status_color,
                'theme_title' => $strategy->strategy_content['week_theme']['title'] ?? null,
                'blog_posts_count' => $strategy->blog_posts_count,
                'social_posts_count' => $strategy->social_posts_count,
                'loops_count' => $strategy->loops_count,
                'completed_at' => $strategy->completed_at?->format('M d, Y'),
            ]);

        return Inertia::render('Strategy/Index', [
            'strategies' => $strategies,
        ]);
    }

    public function show(MarketingStrategy $marketingStrategy): Response|RedirectResponse
    {
        $this->authorize('view', $marketingStrategy);

        return Inertia::render('Strategy/Show', [
            'strategy' => [
                'id' => $marketingStrategy->id,
                'week_start' => $marketingStrategy->week_start->format('M d, Y'),
                'week_end' => $marketingStrategy->week_end->format('M d, Y'),
                'status' => $marketingStrategy->status->value,
                'status_color' => $marketingStrategy->status_color,
                'strategy_content' => $marketingStrategy->strategy_content,
                'converted_items' => $marketingStrategy->converted_items ?? [],
                'completed_at' => $marketingStrategy->completed_at?->format('M d, Y \a\t g:i A'),
            ],
        ]);
    }

    public function convertBlogPost(ConvertBlogPostRequest $request, MarketingStrategy $marketingStrategy): RedirectResponse
    {
        if ($marketingStrategy->status !== MarketingStrategyStatus::Completed) {
            return back()->with('error', 'Strategy must be completed before converting items.');
        }

        $brand = $this->currentBrand();
        $validated = $request->validated();

        $this->strategyService->convertBlogPost(
            $marketingStrategy,
            $brand,
            auth()->id(),
            $validated['index']
        );

        return back()->with('success', 'Blog post draft created.');
    }

    public function convertSocialPost(ConvertSocialPostRequest $request, MarketingStrategy $marketingStrategy): RedirectResponse
    {
        if ($marketingStrategy->status !== MarketingStrategyStatus::Completed) {
            return back()->with('error', 'Strategy must be completed before converting items.');
        }

        $brand = $this->currentBrand();
        $validated = $request->validated();

        $this->strategyService->convertSocialPost(
            $marketingStrategy,
            $brand,
            $validated['index']
        );

        return back()->with('success', 'Social post draft created.');
    }

    public function convertNewsletter(MarketingStrategy $marketingStrategy): RedirectResponse
    {
        $this->authorize('update', $marketingStrategy);

        if ($marketingStrategy->status !== MarketingStrategyStatus::Completed) {
            return back()->with('error', 'Strategy must be completed before converting items.');
        }

        $brand = $this->currentBrand();

        $this->strategyService->convertNewsletter(
            $marketingStrategy,
            $brand,
            auth()->id()
        );

        return back()->with('success', 'Newsletter draft created.');
    }

    public function convertLoop(ConvertLoopRequest $request, MarketingStrategy $marketingStrategy): RedirectResponse
    {
        if ($marketingStrategy->status !== MarketingStrategyStatus::Completed) {
            return back()->with('error', 'Strategy must be completed before converting items.');
        }

        $brand = $this->currentBrand();
        $validated = $request->validated();

        $this->strategyService->convertLoop(
            $marketingStrategy,
            $brand,
            $validated['index']
        );

        return back()->with('success', 'Loop created with suggested items.');
    }

    public function retry(MarketingStrategy $marketingStrategy): RedirectResponse
    {
        $this->authorize('update', $marketingStrategy);

        if ($marketingStrategy->status !== MarketingStrategyStatus::Failed) {
            return back()->with('error', 'Only failed strategies can be retried.');
        }

        $marketingStrategy->update(['status' => MarketingStrategyStatus::Pending]);
        GenerateMarketingStrategy::dispatch($marketingStrategy);

        return back()->with('success', 'Strategy generation restarted.');
    }
}
