<?php

use App\Enums\PostStatus;
use App\Http\Resources\PostResource;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('post resource transforms post correctly', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create([
        'title' => 'Test Post',
        'slug' => 'test-post',
        'excerpt' => 'A test excerpt',
        'content' => [['type' => 'paragraph', 'content' => 'Test content']],
        'content_html' => '<p>Test content</p>',
        'seo_title' => 'SEO Title',
        'seo_description' => 'SEO Description',
        'tags' => ['tag1', 'tag2'],
        'view_count' => 100,
    ]);

    $resource = new PostResource($post);
    $array = $resource->toArray(app(Request::class));

    expect($array)->toHaveKey('id', $post->id)
        ->toHaveKey('title', 'Test Post')
        ->toHaveKey('slug', 'test-post')
        ->toHaveKey('excerpt', 'A test excerpt')
        ->toHaveKey('seo_title', 'SEO Title')
        ->toHaveKey('seo_description', 'SEO Description')
        ->toHaveKey('tags', ['tag1', 'tag2'])
        ->toHaveKey('view_count', 100);
});

test('post resource converts status to value', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->published()->create();

    $resource = new PostResource($post);
    $array = $resource->toArray(app(Request::class));

    expect($array['status'])->toBe(PostStatus::Published->value);
});

test('post resource formats dates correctly', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->published()->create([
        'published_at' => now(),
    ]);

    $resource = new PostResource($post);
    $array = $resource->toArray(app(Request::class));

    expect($array['published_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
    expect($array['published_at_form'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/');
    expect($array['created_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
    expect($array['updated_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
});

test('post resource includes brand when loaded', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create(['name' => 'Test Brand']);
    $post = Post::factory()->forBrand($brand)->create();
    $post->load('brand');

    $resource = new PostResource($post);
    $array = $resource->toArray(app(Request::class));

    expect($array['brand'])->toBeInstanceOf(\Illuminate\Http\Resources\Json\JsonResource::class);
});

test('post resource returns missing value for brand when not loaded', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    $resource = new PostResource($post);
    $array = $resource->toArray(app(Request::class));

    expect($array['brand']->resource)->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
});

test('post resource handles null published_at', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->draft()->create();

    $resource = new PostResource($post);
    $array = $resource->toArray(app(Request::class));

    expect($array['published_at'])->toBeNull();
    expect($array['published_at_form'])->toBeNull();
});
