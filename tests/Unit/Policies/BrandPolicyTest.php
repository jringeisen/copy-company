<?php

use App\Models\Brand;
use App\Models\User;
use App\Policies\BrandPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new BrandPolicy;
});

test('user can view their own brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    expect($this->policy->view($user, $brand))->toBeTrue();
});

test('user cannot view other users brands', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $brand = Brand::factory()->forUser($otherUser)->create();

    expect($this->policy->view($user, $brand))->toBeFalse();
});

test('user can update their own brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    expect($this->policy->update($user, $brand))->toBeTrue();
});

test('user cannot update other users brands', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $brand = Brand::factory()->forUser($otherUser)->create();

    expect($this->policy->update($user, $brand))->toBeFalse();
});

test('user can delete their own brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    expect($this->policy->delete($user, $brand))->toBeTrue();
});

test('user cannot delete other users brands', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $brand = Brand::factory()->forUser($otherUser)->create();

    expect($this->policy->delete($user, $brand))->toBeFalse();
});
