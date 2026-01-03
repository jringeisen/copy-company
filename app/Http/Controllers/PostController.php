<?php

namespace App\Http\Controllers;

use App\Enums\NewsletterSendStatus;
use App\Enums\PostStatus;
use App\Http\Requests\Post\BulkDeletePostsRequest;
use App\Http\Requests\Post\PublishPostRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\PostResource;
use App\Jobs\ProcessNewsletterSend;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $brand = auth()->user()->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        $posts = $brand->posts()
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Posts/Index', [
            'posts' => PostResource::collection($posts)->resolve(),
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $brand = auth()->user()->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        return Inertia::render('Posts/Create', [
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $brand = auth()->user()->currentBrand();
        $validated = $request->validated();

        $post = $brand->posts()->create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'content' => $validated['content'],
            'content_html' => $validated['content_html'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image' => $validated['featured_image'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'publish_to_blog' => $validated['publish_to_blog'] ?? true,
            'send_as_newsletter' => $validated['send_as_newsletter'] ?? true,
            'generate_social' => $validated['generate_social'] ?? true,
            'status' => PostStatus::Draft,
        ]);

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
        $validated = $request->validated();

        $post->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'content_html' => $validated['content_html'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'featured_image' => $validated['featured_image'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'publish_to_blog' => $validated['publish_to_blog'] ?? true,
            'send_as_newsletter' => $validated['send_as_newsletter'] ?? true,
            'generate_social' => $validated['generate_social'] ?? true,
        ]);

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
        $brand = auth()->user()->currentBrand();

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
            $post->update([
                'status' => PostStatus::Scheduled,
                'scheduled_at' => $validated['scheduled_at'],
                'publish_to_blog' => $validated['publish_to_blog'] ?? true,
                'send_as_newsletter' => $validated['send_as_newsletter'] ?? false,
            ]);

            if ($validated['send_as_newsletter'] ?? false) {
                $post->newsletterSend()->create([
                    'brand_id' => $post->brand_id,
                    'subject_line' => $validated['subject_line'],
                    'preview_text' => $validated['preview_text'] ?? null,
                    'provider' => $post->brand->newsletter_provider,
                    'status' => NewsletterSendStatus::Scheduled,
                    'scheduled_at' => $validated['scheduled_at'],
                ]);
            }

            return redirect()->route('posts.index')->with('success', 'Post scheduled for '.\Carbon\Carbon::parse($validated['scheduled_at'])->format('M d, Y \a\t g:i A').'!');
        }

        $post->update([
            'status' => PostStatus::Published,
            'published_at' => now(),
            'publish_to_blog' => $validated['publish_to_blog'] ?? true,
            'send_as_newsletter' => $validated['send_as_newsletter'] ?? false,
        ]);

        if ($validated['send_as_newsletter'] ?? false) {
            $newsletterSend = $post->newsletterSend()->create([
                'brand_id' => $post->brand_id,
                'subject_line' => $validated['subject_line'],
                'preview_text' => $validated['preview_text'] ?? null,
                'provider' => $post->brand->newsletter_provider,
                'status' => NewsletterSendStatus::Scheduled,
                'scheduled_at' => now(),
            ]);

            ProcessNewsletterSend::dispatch($newsletterSend);
        }

        return redirect()->route('posts.index')->with('success', 'Post published!');
    }
}
