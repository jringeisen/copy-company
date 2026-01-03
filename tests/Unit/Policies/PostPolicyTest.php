<?php

use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new PostPolicy;
});

test('user can view any posts if they have a brand', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('user cannot view any posts if they have no brand', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse();
});

test('user can view their own brand posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    expect($this->policy->view($user, $post))->toBeTrue();
});

test('user cannot view posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    expect($this->policy->view($user, $post))->toBeFalse();
});

test('user can create posts if they have a brand', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    expect($this->policy->create($user))->toBeTrue();
});

test('user cannot create posts if they have no brand', function () {
    $user = User::factory()->create();

    expect($this->policy->create($user))->toBeFalse();
});

test('user can update their own brand posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    expect($this->policy->update($user, $post))->toBeTrue();
});

test('user cannot update posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    expect($this->policy->update($user, $post))->toBeFalse();
});

test('user can delete their own brand posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    expect($this->policy->delete($user, $post))->toBeTrue();
});

test('user cannot delete posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    expect($this->policy->delete($user, $post))->toBeFalse();
});
