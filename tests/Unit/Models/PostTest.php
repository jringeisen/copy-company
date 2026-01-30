<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\Post;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('post belongs to a brand', function () {
    $account = Account::factory()->create();
    $brand = Brand::factory()->forAccount($account)->create();
    $post = Post::factory()->forBrand($brand)->create();

    expect($post->brand)->toBeInstanceOf(Brand::class);
});

test('post belongs to a user', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $brand = Brand::factory()->forAccount($account)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create();

    expect($post->user)->toBeInstanceOf(User::class);
    expect($post->user->id)->toBe($user->id);
});

test('post has many social posts', function () {
    $post = Post::factory()->create();
    SocialPost::factory()->count(3)->forPost($post)->create();

    expect($post->socialPosts)->toHaveCount(3);
});

test('post generates slug from title on creation', function () {
    $brand = Brand::factory()->create();
    $post = Post::factory()->forBrand($brand)->create([
        'title' => 'My Amazing Blog Post',
        'slug' => null,
    ]);

    expect($post->slug)->toBe('my-amazing-blog-post');
});

test('published scope filters published posts', function () {
    $brand = Brand::factory()->create();
    Post::factory()->forBrand($brand)->published()->count(2)->create();
    Post::factory()->forBrand($brand)->draft()->count(3)->create();

    expect(Post::published()->count())->toBe(2);
});

test('draft scope filters draft posts', function () {
    $brand = Brand::factory()->create();
    Post::factory()->forBrand($brand)->published()->count(2)->create();
    Post::factory()->forBrand($brand)->draft()->count(3)->create();

    expect(Post::draft()->count())->toBe(3);
});

test('scheduled scope filters scheduled posts', function () {
    $brand = Brand::factory()->create();
    Post::factory()->forBrand($brand)->scheduled()->count(2)->create();
    Post::factory()->forBrand($brand)->draft()->count(1)->create();

    expect(Post::scheduled()->count())->toBe(2);
});

test('post url is constructed from brand url and slug', function () {
    $brand = Brand::factory()->create(['slug' => 'my-brand']);
    $post = Post::factory()->forBrand($brand)->create(['slug' => 'my-post']);

    expect($post->url)->toContain('/blog/my-brand/my-post');
});

test('is published returns true for published status', function () {
    $post = Post::factory()->published()->create();

    expect($post->isPublished())->toBeTrue();
});

test('is published returns false for draft status', function () {
    $post = Post::factory()->draft()->create();

    expect($post->isPublished())->toBeFalse();
});

test('post generates unique slug when duplicate exists', function () {
    $brand = Brand::factory()->create();

    $post1 = Post::factory()->forBrand($brand)->create([
        'title' => 'My Amazing Blog Post',
        'slug' => null,
    ]);

    $post2 = Post::factory()->forBrand($brand)->create([
        'title' => 'My Amazing Blog Post',
        'slug' => null,
    ]);

    $post3 = Post::factory()->forBrand($brand)->create([
        'title' => 'My Amazing Blog Post',
        'slug' => null,
    ]);

    expect($post1->slug)->toBe('my-amazing-blog-post');
    expect($post2->slug)->toBe('my-amazing-blog-post-1');
    expect($post3->slug)->toBe('my-amazing-blog-post-2');
});

test('same slug can be used by different brands', function () {
    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();

    $post1 = Post::factory()->forBrand($brand1)->create([
        'title' => 'My Amazing Blog Post',
        'slug' => null,
    ]);

    $post2 = Post::factory()->forBrand($brand2)->create([
        'title' => 'My Amazing Blog Post',
        'slug' => null,
    ]);

    expect($post1->slug)->toBe('my-amazing-blog-post');
    expect($post2->slug)->toBe('my-amazing-blog-post');
});
