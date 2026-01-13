<?php

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Http\Resources\SocialPostResource;
use App\Models\Brand;
use App\Models\Post;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('social post resource transforms social post correctly', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create([
        'platform' => SocialPlatform::Instagram,
        'format' => SocialFormat::Feed,
        'content' => 'Test content',
        'hashtags' => ['test', 'hashtag'],
        'link' => 'https://example.com',
        'ai_generated' => true,
        'user_edited' => false,
    ]);

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array)->toHaveKey('id', $socialPost->id)
        ->toHaveKey('platform', SocialPlatform::Instagram->value)
        ->toHaveKey('format', SocialFormat::Feed->value)
        ->toHaveKey('content', 'Test content')
        ->toHaveKey('hashtags', ['test', 'hashtag'])
        ->toHaveKey('link', 'https://example.com')
        ->toHaveKey('ai_generated', true)
        ->toHaveKey('user_edited', false);
});

test('social post resource includes platform display name', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->forPlatform(SocialPlatform::Facebook)->create();

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['platform_display'])->toBe('Facebook');
});

test('social post resource includes status color', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->published()->create();

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['status_color'])->toBe('green');
});

test('social post resource includes character limit', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->forPlatform(SocialPlatform::Facebook)->create();

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['character_limit'])->toBe(63206);
});

test('social post resource converts status to value', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->scheduled()->create();

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['status'])->toBe(SocialPostStatus::Scheduled->value);
});

test('social post resource formats dates correctly', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->scheduled()->create([
        'scheduled_at' => now()->addDay(),
    ]);

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['scheduled_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
    expect($array['scheduled_at_form'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/');
    expect($array['created_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
});

test('social post resource includes post when loaded', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create(['title' => 'Original Post']);
    $socialPost = SocialPost::factory()->forBrand($brand)->forPost($post)->create();
    $socialPost->load('post');

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['post'])->toHaveKey('id', $post->id)
        ->toHaveKey('title', 'Original Post');
});

test('social post resource returns missing value for post when null', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create([
        'post_id' => null,
    ]);

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['post'])->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
});

test('social post resource handles null scheduled_at', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['scheduled_at'])->toBeNull();
    expect($array['scheduled_at_form'])->toBeNull();
});

test('social post resource returns empty array when media is empty', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create([
        'media' => [],
    ]);

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['media'])->toBe([]);
});

test('social post resource returns legacy media array as-is', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    // Legacy data format: array of objects with id, url, etc.
    $legacyMedia = [
        ['id' => 1, 'url' => 'https://example.com/image1.jpg', 'thumbnail_url' => 'https://example.com/thumb1.jpg'],
        ['id' => 2, 'url' => 'https://example.com/image2.jpg', 'thumbnail_url' => 'https://example.com/thumb2.jpg'],
    ];
    $socialPost = SocialPost::factory()->forBrand($brand)->create([
        'media' => $legacyMedia,
    ]);

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['media'])->toBe($legacyMedia);
});

test('social post resource hydrates media ids to full media objects', function () {
    Illuminate\Support\Facades\Storage::fake('public');

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = App\Models\Media::factory()->forBrand($brand)->create([
        'disk' => 'public',
        'path' => 'media/test-image.jpg',
        'thumbnail_path' => 'media/thumbnails/test-thumb.jpg',
    ]);

    $socialPost = SocialPost::factory()->forBrand($brand)->create([
        'media' => [$media->id],
    ]);

    $resource = new SocialPostResource($socialPost);
    $array = $resource->toArray(app(Request::class));

    expect($array['media'])->toHaveCount(1);
    expect($array['media'][0]['id'])->toBe($media->id);
    expect($array['media'][0])->toHaveKey('url');
    expect($array['media'][0])->toHaveKey('thumbnail_url');
});
