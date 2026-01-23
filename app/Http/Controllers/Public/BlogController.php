<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Brand $brand): Response
    {
        $posts = $brand->posts()
            ->published()
            ->where('publish_to_blog', true)
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (Post $post): array => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'featured_image' => $post->featured_image,
                'published_at' => $post->published_at->format('M d, Y'),
            ])
            ->toArray();

        return Inertia::render('Public/Blog/Index', [
            'brand' => [
                'name' => $brand->name,
                'slug' => $brand->slug,
                'tagline' => $brand->tagline,
                'description' => $brand->description,
                'primary_color' => $brand->primary_color,
                'logo_path' => $brand->logo_path,
            ],
            'posts' => $posts,
            'canonicalUrl' => url("/blog/{$brand->slug}"),
            'appUrl' => config('app.url'),
        ]);
    }

    public function show(Brand $brand, Post $post): Response
    {
        // Ensure post belongs to brand and is published
        if ($post->brand_id !== $brand->id || ! $post->isPublished()) {
            abort(404);
        }

        // Increment view count
        $post->increment('view_count');

        $featuredImage = $post->featured_image;
        if ($featuredImage && ! str_starts_with($featuredImage, 'http')) {
            $featuredImage = config('app.url').$featuredImage;
        }

        return Inertia::render('Public/Blog/Show', [
            'brand' => [
                'name' => $brand->name,
                'slug' => $brand->slug,
                'tagline' => $brand->tagline,
                'primary_color' => $brand->primary_color,
                'logo_path' => $brand->logo_path,
            ],
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'content_html' => $post->content_html,
                'excerpt' => $post->excerpt,
                'featured_image' => $featuredImage,
                'published_at' => $post->published_at->format('F d, Y'),
                'published_at_iso' => $post->published_at->toIso8601String(),
                'seo_title' => $post->seo_title,
                'seo_description' => $post->seo_description,
            ],
            'canonicalUrl' => url("/blog/{$brand->slug}/{$post->slug}"),
            'appUrl' => config('app.url'),
        ]);
    }
}
