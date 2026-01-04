<?php

use App\Models\Brand;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('guests cannot access dashboard', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect('/login');
});

test('users without brand can view dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Dashboard')
        ->has('user')
        ->where('brand', null)
        ->where('stats.postsCount', 0)
        ->where('stats.subscribersCount', 0)
        ->where('stats.draftsCount', 0)
    );
});

test('users with brand can view dashboard', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Dashboard')
        ->has('user')
        ->has('brand')
        ->has('stats')
    );
});

test('dashboard shows correct posts count', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Post::factory()->forBrand($brand)->count(5)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('stats.postsCount', 5)
    );
});

test('dashboard shows correct drafts count', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Post::factory()->forBrand($brand)->draft()->count(3)->create();
    Post::factory()->forBrand($brand)->published()->count(2)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('stats.postsCount', 5)
        ->where('stats.draftsCount', 3)
    );
});

test('dashboard shows correct confirmed subscribers count', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Subscriber::factory()->forBrand($brand)->confirmed()->count(4)->create();
    Subscriber::factory()->forBrand($brand)->pending()->count(2)->create();
    Subscriber::factory()->forBrand($brand)->unsubscribed()->count(1)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('stats.subscribersCount', 4)
    );
});

test('dashboard only shows stats for current brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Post::factory()->forBrand($brand)->count(3)->create();

    // Create another brand with posts that should not be counted
    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    Post::factory()->forBrand($otherBrand)->count(10)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('stats.postsCount', 3)
    );
});
