<?php

use App\Models\Brand;
use App\Models\Subscriber;
use App\Models\User;
use App\Policies\SubscriberPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new SubscriberPolicy;
});

test('user can view their own brand subscribers', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    expect($this->policy->view($user, $subscriber))->toBeTrue();
});

test('user cannot view subscribers from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    expect($this->policy->view($user, $subscriber))->toBeFalse();
});

test('user can delete their own brand subscribers', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    expect($this->policy->delete($user, $subscriber))->toBeTrue();
});

test('user cannot delete subscribers from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    expect($this->policy->delete($user, $subscriber))->toBeFalse();
});
