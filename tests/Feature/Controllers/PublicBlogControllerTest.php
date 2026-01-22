<?php

use App\Models\Brand;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public blog index shows published posts for a brand', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    Post::factory()->forBrand($brand)->published()->count(3)->create();
    Post::factory()->forBrand($brand)->draft()->count(2)->create();

    $response = $this->get("/blog/{$brand->slug}");

    $response->assertStatus(200);
});

test('public blog index returns 404 for non-existent brand', function () {
    $response = $this->get('/blog/non-existent-brand');

    $response->assertStatus(404);
});

test('public blog post shows a published post', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    $post = Post::factory()->forBrand($brand)->published()->create(['slug' => 'my-post']);

    $response = $this->get("/blog/{$brand->slug}/{$post->slug}");

    $response->assertStatus(200);
});

test('public blog post returns 404 for draft posts', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    $post = Post::factory()->forBrand($brand)->draft()->create(['slug' => 'draft-post']);

    $response = $this->get("/blog/{$brand->slug}/{$post->slug}");

    $response->assertStatus(404);
});

test('public blog post returns 404 for non-existent posts', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);

    $response = $this->get("/blog/{$brand->slug}/non-existent-post");

    $response->assertStatus(404);
});
