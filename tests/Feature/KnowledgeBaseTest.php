<?php

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('help index page is accessible', function () {
    $category = KnowledgeBaseCategory::factory()->create();
    KnowledgeBaseArticle::factory()->published()->create(['category_id' => $category->id]);

    $response = $this->get('/help');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Public/Help/Index'));
});

test('help index page passes categories with published articles', function () {
    $category = KnowledgeBaseCategory::factory()->create();
    $published = KnowledgeBaseArticle::factory()->published()->create(['category_id' => $category->id]);
    KnowledgeBaseArticle::factory()->draft()->create(['category_id' => $category->id]);

    $response = $this->get('/help');

    $response->assertInertia(fn ($page) => $page
        ->component('Public/Help/Index')
        ->has('categories', 1)
        ->where('categories.0.articles', function ($articles) use ($published) {
            return count($articles) === 1 && $articles[0]['id'] === $published->id;
        })
    );
});

test('help article page loads with correct props', function () {
    $category = KnowledgeBaseCategory::factory()->create(['slug' => 'test-category']);
    $article = KnowledgeBaseArticle::factory()->published()->create([
        'category_id' => $category->id,
        'slug' => 'test-article',
    ]);
    $sibling = KnowledgeBaseArticle::factory()->published()->create([
        'category_id' => $category->id,
        'slug' => 'sibling-article',
    ]);

    $response = $this->get('/help/test-category/test-article');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Public/Help/Show')
        ->has('article')
        ->where('article.id', $article->id)
        ->has('category')
        ->where('category.slug', 'test-category')
        ->has('siblingArticles', 2)
    );
});

test('unpublished articles return 404', function () {
    $category = KnowledgeBaseCategory::factory()->create(['slug' => 'test-category']);
    KnowledgeBaseArticle::factory()->draft()->create([
        'category_id' => $category->id,
        'slug' => 'draft-article',
    ]);

    $response = $this->get('/help/test-category/draft-article');

    $response->assertNotFound();
});

test('invalid category slug returns 404', function () {
    $response = $this->get('/help/nonexistent-category/some-article');

    $response->assertNotFound();
});

test('invalid article slug returns 404', function () {
    $category = KnowledgeBaseCategory::factory()->create(['slug' => 'test-category']);

    $response = $this->get('/help/test-category/nonexistent-article');

    $response->assertNotFound();
});

test('help routes are named correctly', function () {
    expect(route('help.index'))->toEndWith('/help');
    expect(route('help.show', ['categorySlug' => 'cat', 'articleSlug' => 'art']))->toEndWith('/help/cat/art');
});

test('contextual help link URLs resolve when articles exist', function (string $categorySlug, string $articleSlug) {
    $category = KnowledgeBaseCategory::factory()->create(['slug' => $categorySlug]);
    KnowledgeBaseArticle::factory()->published()->create([
        'category_id' => $category->id,
        'slug' => $articleSlug,
    ]);

    $response = $this->get("/help/{$categorySlug}/{$articleSlug}");

    $response->assertSuccessful();
})->with([
    'brand settings' => ['getting-started', 'customizing-your-brand-settings'],
    'email domain' => ['custom-domains', 'setting-up-a-custom-email-domain'],
    'sprints index' => ['content-sprints', 'what-are-content-sprints'],
    'sprints create' => ['content-sprints', 'creating-a-content-sprint'],
    'sprints show' => ['content-sprints', 'turning-ideas-into-draft-posts'],
    'posts writing' => ['posts', 'writing-your-first-post'],
    'posts publishing' => ['posts', 'publishing-and-scheduling-posts'],
    'posts managing' => ['posts', 'managing-your-posts'],
]);
