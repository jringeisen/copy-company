<?php

use App\Enums\SocialPlatform;
use App\Models\Brand;
use App\Models\Post;
use App\Models\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('social post belongs to a brand', function () {
    $socialPost = SocialPost::factory()->create();

    expect($socialPost->brand)->toBeInstanceOf(Brand::class);
});

test('social post can belong to a post', function () {
    $post = Post::factory()->create();
    $socialPost = SocialPost::factory()->forPost($post)->create();

    expect($socialPost->post)->toBeInstanceOf(Post::class);
    expect($socialPost->post->id)->toBe($post->id);
});

test('draft scope filters draft social posts', function () {
    $brand = Brand::factory()->create();
    SocialPost::factory()->forBrand($brand)->draft()->count(2)->create();
    SocialPost::factory()->forBrand($brand)->published()->count(1)->create();

    expect(SocialPost::draft()->count())->toBe(2);
});

test('queued scope filters queued social posts', function () {
    $brand = Brand::factory()->create();
    SocialPost::factory()->forBrand($brand)->queued()->count(3)->create();
    SocialPost::factory()->forBrand($brand)->draft()->count(1)->create();

    expect(SocialPost::queued()->count())->toBe(3);
});

test('for platform scope filters by platform', function () {
    $brand = Brand::factory()->create();
    SocialPost::factory()->forBrand($brand)->forPlatform(SocialPlatform::Instagram)->count(2)->create();
    SocialPost::factory()->forBrand($brand)->forPlatform(SocialPlatform::Facebook)->count(3)->create();

    expect(SocialPost::forPlatform(SocialPlatform::Instagram)->count())->toBe(2);
    expect(SocialPost::forPlatform(SocialPlatform::Facebook)->count())->toBe(3);
});

test('is published returns true for published status', function () {
    $socialPost = SocialPost::factory()->published()->create();

    expect($socialPost->isPublished())->toBeTrue();
});

test('can publish returns true for draft queued and scheduled statuses', function () {
    $draft = SocialPost::factory()->draft()->create();
    $queued = SocialPost::factory()->queued()->create();
    $scheduled = SocialPost::factory()->scheduled()->create();
    $published = SocialPost::factory()->published()->create();

    expect($draft->canPublish())->toBeTrue();
    expect($queued->canPublish())->toBeTrue();
    expect($scheduled->canPublish())->toBeTrue();
    expect($published->canPublish())->toBeFalse();
});

test('character limit returns correct value for each platform', function () {
    $facebook = SocialPost::factory()->forPlatform(SocialPlatform::Facebook)->create();
    $instagram = SocialPost::factory()->forPlatform(SocialPlatform::Instagram)->create();
    $linkedin = SocialPost::factory()->forPlatform(SocialPlatform::LinkedIn)->create();

    expect($facebook->character_limit)->toBe(63206);
    expect($instagram->character_limit)->toBe(2200);
    expect($linkedin->character_limit)->toBe(3000);
});

test('platform display name returns formatted name', function () {
    $facebook = SocialPost::factory()->forPlatform(SocialPlatform::Facebook)->create();
    $linkedin = SocialPost::factory()->forPlatform(SocialPlatform::LinkedIn)->create();

    expect($facebook->platform_display_name)->toBe('Facebook');
    expect($linkedin->platform_display_name)->toBe('LinkedIn');
});

test('status color returns correct color', function () {
    $draft = SocialPost::factory()->draft()->create();
    $published = SocialPost::factory()->published()->create();
    $failed = SocialPost::factory()->failed()->create();

    expect($draft->status_color)->toBe('gray');
    expect($published->status_color)->toBe('green');
    expect($failed->status_color)->toBe('red');
});
