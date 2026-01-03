<?php

use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Policies\SocialPostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new SocialPostPolicy;
});

test('user can view their own brand social posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();

    expect($this->policy->view($user, $socialPost))->toBeTrue();
});

test('user cannot view social posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $socialPost = SocialPost::factory()->forBrand($otherBrand)->create();

    expect($this->policy->view($user, $socialPost))->toBeFalse();
});

test('user can update their own brand social posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();

    expect($this->policy->update($user, $socialPost))->toBeTrue();
});

test('user cannot update social posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $socialPost = SocialPost::factory()->forBrand($otherBrand)->create();

    expect($this->policy->update($user, $socialPost))->toBeFalse();
});

test('user can delete their own brand social posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();

    expect($this->policy->delete($user, $socialPost))->toBeTrue();
});

test('user cannot delete social posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $socialPost = SocialPost::factory()->forBrand($otherBrand)->create();

    expect($this->policy->delete($user, $socialPost))->toBeFalse();
});
