<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\SocialPost\BulkScheduleSocialPostRequest;
use App\Http\Requests\SocialPost\ScheduleSocialPostRequest;
use App\Http\Requests\SocialPost\StoreSocialPostRequest;
use App\Http\Requests\SocialPost\UpdateSocialPostRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\SocialPostResource;
use App\Jobs\PublishSocialPost;
use App\Models\SocialPost;
use App\Services\SocialPublishing\SocialPublishingService;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SocialPostController extends Controller
{
    use HasBrandAuthorization;

    public function __construct(
        protected TokenManager $tokenManager,
        protected SocialPublishingService $publishingService
    ) {}

    public function index(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $query = $brand->socialPosts()->with('post:id,title');

        if (request()->has('platform') && request()->platform !== 'all') {
            $query->forPlatform(request()->platform);
        }

        if (request()->has('status') && request()->status !== 'all') {
            $query->where('status', request()->status);
        }

        $socialPosts = $query
            ->orderByDesc('created_at')
            ->paginate(20);

        $posts = $brand->posts()
            ->whereIn('status', [PostStatus::Published, PostStatus::Draft])
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'status']);

        // Get fully configured platforms (connected AND have page/board selected where required)
        $connectedPlatforms = $this->getFullyConfiguredPlatforms($brand);

        return Inertia::render('Social/Index', [
            'socialPosts' => SocialPostResource::collection($socialPosts),
            'posts' => $posts,
            'brand' => (new BrandResource($brand))->resolve(),
            'filters' => [
                'platform' => request()->platform ?? 'all',
                'status' => request()->status ?? 'all',
            ],
            'platforms' => SocialPlatform::toDropdownOptions(includeAll: true),
            'statuses' => SocialPostStatus::toDropdownOptions(includeAll: true),
            'connectedPlatforms' => $connectedPlatforms,
        ]);
    }

    public function queue(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $queuedPosts = $brand->socialPosts()
            ->with('post:id,title')
            ->where('status', SocialPostStatus::Queued)
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Social/Queue', [
            'queuedPosts' => SocialPostResource::collection($queuedPosts)->resolve(),
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }

    public function store(StoreSocialPostRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();
        $validated = $request->validated();

        $brand->socialPosts()->create([
            'post_id' => $validated['post_id'] ?? null,
            'platform' => $validated['platform'],
            'format' => $validated['format'] ?? 'feed',
            'content' => $validated['content'],
            'hashtags' => $validated['hashtags'] ?? [],
            'link' => $validated['link'] ?? null,
            'status' => isset($validated['status']) ? SocialPostStatus::from($validated['status']) : SocialPostStatus::Draft,
            'ai_generated' => $validated['ai_generated'] ?? false,
        ]);

        return back()->with('success', 'Social post created!');
    }

    public function update(UpdateSocialPostRequest $request, SocialPost $socialPost): RedirectResponse
    {
        $validated = $request->validated();

        $socialPost->update([
            'content' => $validated['content'],
            'hashtags' => $validated['hashtags'] ?? [],
            'link' => $validated['link'] ?? null,
            'format' => $validated['format'] ?? $socialPost->format,
            'user_edited' => true,
        ]);

        return back()->with('success', 'Social post updated!');
    }

    public function destroy(SocialPost $socialPost): RedirectResponse
    {
        $this->authorize('delete', $socialPost);

        $socialPost->delete();

        return back()->with('success', 'Social post deleted.');
    }

    public function queuePost(SocialPost $socialPost): RedirectResponse
    {
        $this->authorize('update', $socialPost);

        if ($socialPost->status !== SocialPostStatus::Draft) {
            return back()->with('error', 'Only draft posts can be queued.');
        }

        $socialPost->update(['status' => SocialPostStatus::Queued]);

        return back()->with('success', 'Post added to queue!');
    }

    public function schedule(ScheduleSocialPostRequest $request, SocialPost $socialPost): RedirectResponse
    {
        $this->authorize('update', $socialPost);

        if (! in_array($socialPost->status, [SocialPostStatus::Draft, SocialPostStatus::Queued])) {
            return back()->with('error', 'This post cannot be scheduled.');
        }

        $validated = $request->validated();

        $socialPost->update([
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return back()->with('success', 'Post scheduled for '.\Carbon\Carbon::parse($validated['scheduled_at'])->format('M d, Y \a\t g:i A').'!');
    }

    public function bulkSchedule(BulkScheduleSocialPostRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();
        $validated = $request->validated();

        $scheduledAt = \Carbon\Carbon::parse($validated['scheduled_at']);
        $intervalMinutes = $validated['interval_minutes'] ?? 0;

        $socialPosts = SocialPost::whereIn('id', $validated['social_post_ids'])
            ->where('brand_id', $brand->id)
            ->whereIn('status', [SocialPostStatus::Draft, SocialPostStatus::Queued])
            ->get();

        foreach ($socialPosts as $index => $socialPost) {
            $postScheduledAt = $scheduledAt->copy()->addMinutes($index * $intervalMinutes);
            $socialPost->update([
                'status' => SocialPostStatus::Scheduled,
                'scheduled_at' => $postScheduledAt,
            ]);
        }

        return back()->with('success', count($socialPosts).' posts scheduled!');
    }

    public function publish(SocialPost $socialPost): RedirectResponse
    {
        $this->authorize('update', $socialPost);

        if (! $socialPost->canPublish()) {
            return back()->with('error', 'This post cannot be published.');
        }

        $brand = $socialPost->brand;
        $platform = $socialPost->platform->value;

        // Check if platform is connected
        if (! $this->tokenManager->isConnected($brand, $platform)) {
            return back()->with('error', 'Please connect your '.$socialPost->platform->displayName().' account first.');
        }

        // Dispatch the publishing job
        PublishSocialPost::dispatch($socialPost);

        return back()->with('success', 'Publishing to '.$socialPost->platform->displayName().'...');
    }

    public function publishNow(SocialPost $socialPost): RedirectResponse
    {
        $this->authorize('update', $socialPost);

        if (! $socialPost->canPublish()) {
            return back()->with('error', 'This post cannot be published.');
        }

        $brand = $socialPost->brand;
        $platform = $socialPost->platform->value;

        // Check if platform is connected
        if (! $this->tokenManager->isConnected($brand, $platform)) {
            return back()->with('error', 'Please connect your '.$socialPost->platform->displayName().' account first.');
        }

        // Publish synchronously
        $success = $this->publishingService->publishAndUpdateStatus($socialPost);

        if ($success) {
            return back()->with('success', 'Published to '.$socialPost->platform->displayName().'!');
        }

        return back()->with('error', 'Failed to publish: '.$socialPost->failure_reason);
    }

    public function retry(SocialPost $socialPost): RedirectResponse
    {
        $this->authorize('update', $socialPost);

        if ($socialPost->status !== SocialPostStatus::Failed) {
            return back()->with('error', 'Only failed posts can be retried.');
        }

        $brand = $socialPost->brand;
        $platform = $socialPost->platform->value;

        if (! $this->tokenManager->isConnected($brand, $platform)) {
            return back()->with('error', 'Please connect your '.$socialPost->platform->displayName().' account first.');
        }

        // Reset status and dispatch
        $socialPost->update([
            'status' => SocialPostStatus::Queued,
            'failure_reason' => null,
        ]);

        PublishSocialPost::dispatch($socialPost);

        return back()->with('success', 'Retrying publication to '.$socialPost->platform->displayName().'...');
    }

    /**
     * Get list of fully configured platform identifiers.
     *
     * @return array<string>
     */
    protected function getFullyConfiguredPlatforms(\App\Models\Brand $brand): array
    {
        $configuredPlatforms = [];

        foreach (SocialPlatform::cases() as $platform) {
            $identifier = $platform->value;
            $credentials = $this->tokenManager->getCredentials($brand, $identifier);

            if (! $credentials) {
                continue;
            }

            // Facebook requires page_id to be configured
            if ($identifier === 'facebook' && empty($credentials['page_id'])) {
                continue;
            }

            // Pinterest requires board_id to be configured
            if ($identifier === 'pinterest' && empty($credentials['board_id'])) {
                continue;
            }

            $configuredPlatforms[] = $identifier;
        }

        return $configuredPlatforms;
    }
}
