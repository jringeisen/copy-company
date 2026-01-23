<?php

use App\Models\Brand;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sitemap returns xml response', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/xml');
});

test('sitemap includes static pages', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $content = $response->getContent();
    expect($content)->toContain(config('app.url'));
    expect($content)->toContain('/privacy-policy');
    expect($content)->toContain('/terms-of-service');
});

test('sitemap includes published blog posts', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    $post = Post::factory()->forBrand($brand)->published()->create([
        'slug' => 'published-post',
        'publish_to_blog' => true,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $content = $response->getContent();
    expect($content)->toContain("/blog/{$brand->slug}/{$post->slug}");
    expect($content)->toContain("/blog/{$brand->slug}");
});

test('sitemap excludes draft posts', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    Post::factory()->forBrand($brand)->draft()->create([
        'slug' => 'draft-post',
        'publish_to_blog' => true,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $content = $response->getContent();
    expect($content)->not->toContain('/blog/test-brand/draft-post');
});

test('sitemap excludes posts not published to blog', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    Post::factory()->forBrand($brand)->published()->create([
        'slug' => 'newsletter-only',
        'publish_to_blog' => false,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $content = $response->getContent();
    expect($content)->not->toContain('/blog/test-brand/newsletter-only');
});

test('sitemap excludes brands without published posts', function () {
    $brand = Brand::factory()->create(['slug' => 'empty-brand']);
    Post::factory()->forBrand($brand)->draft()->create(['publish_to_blog' => true]);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $content = $response->getContent();
    expect($content)->not->toContain('/blog/empty-brand</loc>');
});
