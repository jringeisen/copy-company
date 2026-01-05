<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\Post\BulkDeletePostsRequest;
use App\Http\Requests\Post\PublishPostRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    use HasBrandAuthorization;

    public function __construct(
        protected PostService $postService
    ) {}

    public function index(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $posts = $brand->posts()
            ->with('brand') // Eager load brand to avoid N+1 in PostResource url accessor
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Posts/Index', [
            'posts' => PostResource::collection($posts)->resolve(),
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        return Inertia::render('Posts/Create', [
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();
        $post = $this->postService->create($brand, auth()->id(), $request->validated());

        return redirect()->route('posts.edit', $post)->with('success', 'Post created!');
    }

    public function edit(Post $post): Response
    {
        $this->authorize('view', $post);

        $post->load('brand');

        return Inertia::render('Posts/Edit', [
            'post' => (new PostResource($post))->resolve(),
            'brand' => (new BrandResource($post->brand))->resolve(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->postService->update($post, $request->validated());

        return back()->with('success', 'Post saved!');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Post deleted.');
    }

    public function bulkDestroy(BulkDeletePostsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $brand = $this->currentBrand();

        $deleted = Post::whereIn('id', $validated['ids'])
            ->where('brand_id', $brand->id)
            ->delete();

        return redirect()->route('posts.index')
            ->with('success', "{$deleted} post(s) deleted.");
    }

    public function publish(PublishPostRequest $request, Post $post): RedirectResponse
    {
        $validated = $request->validated();
        $isScheduled = ($validated['schedule_mode'] ?? 'now') === 'scheduled';

        if ($isScheduled) {
            $this->postService->schedule($post, $validated);

            return redirect()->route('posts.index')->with(
                'success',
                'Post scheduled for '.\Carbon\Carbon::parse($validated['scheduled_at'])->format('M d, Y \a\t g:i A').'!'
            );
        }

        $this->postService->publish($post, $validated);

        return redirect()->route('posts.index')->with('success', 'Post published!');
    }
}
