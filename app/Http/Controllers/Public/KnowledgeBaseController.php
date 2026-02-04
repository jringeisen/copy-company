<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseCategory;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeBaseController extends Controller
{
    public function index(): Response
    {
        $categories = KnowledgeBaseCategory::query()
            ->with('publishedArticles')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (KnowledgeBaseCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'articles' => $category->publishedArticles->map(fn ($article): array => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                ])->toArray(),
            ])
            ->filter(fn (array $category): bool => count($category['articles']) > 0)
            ->values()
            ->toArray();

        return Inertia::render('Public/Help/Index', [
            'categories' => $categories,
            'appUrl' => config('app.url'),
        ]);
    }

    public function show(string $categorySlug, string $articleSlug): Response
    {
        $category = KnowledgeBaseCategory::query()
            ->where('slug', $categorySlug)
            ->firstOrFail();

        $article = $category->articles()
            ->published()
            ->where('slug', $articleSlug)
            ->firstOrFail();

        $siblingArticles = $category->publishedArticles
            ->map(fn ($sibling): array => [
                'id' => $sibling->id,
                'title' => $sibling->title,
                'slug' => $sibling->slug,
            ])
            ->toArray();

        return Inertia::render('Public/Help/Show', [
            'category' => [
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'content_html' => $article->content_html,
                'excerpt' => $article->excerpt,
                'seo_title' => $article->seo_title,
                'seo_description' => $article->seo_description,
            ],
            'siblingArticles' => $siblingArticles,
            'appUrl' => config('app.url'),
        ]);
    }
}
