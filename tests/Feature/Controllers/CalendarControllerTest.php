<?php

use App\Enums\SocialPlatform;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('guests cannot access calendar', function () {
    $response = $this->get(route('calendar.index'));

    $response->assertRedirect('/login');
});

test('users without brand are redirected to brand create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertRedirect(route('brands.create'));
});

test('users with brand can view calendar', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Calendar/Index')
        ->has('events')
        ->has('currentMonth')
        ->has('brand')
    );
});

test('calendar uses current month by default', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('currentMonth', now()->format('Y-m'))
    );
});

test('calendar accepts custom month parameter', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('calendar.index', ['month' => '2025-06']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('currentMonth', '2025-06')
    );
});

test('calendar shows scheduled posts as events', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    Post::factory()->forBrand($brand)->scheduled()->create([
        'title' => 'Scheduled Blog Post',
        'scheduled_at' => now()->startOfMonth()->addDays(10),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 1)
        ->where('events.0.type', 'post')
        ->where('events.0.title', 'Scheduled Blog Post')
    );
});

test('calendar shows published posts as events', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    Post::factory()->forBrand($brand)->published()->create([
        'title' => 'Published Blog Post',
        'published_at' => now()->startOfMonth()->addDays(5),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 1)
        ->where('events.0.type', 'post')
        ->where('events.0.title', 'Published Blog Post')
    );
});

test('calendar shows scheduled social posts as events', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    SocialPost::factory()->forBrand($brand)->scheduled()->create([
        'platform' => SocialPlatform::Twitter,
        'content' => 'This is a scheduled tweet about something interesting',
        'scheduled_at' => now()->startOfMonth()->addDays(15),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 1)
        ->where('events.0.type', 'social')
    );
});

test('calendar shows published social posts as events', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    SocialPost::factory()->forBrand($brand)->published()->create([
        'platform' => SocialPlatform::Instagram,
        'published_at' => now()->startOfMonth()->addDays(8),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 1)
        ->where('events.0.type', 'social')
    );
});

test('calendar shows scheduled newsletter sends as events', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->draft()->create(); // Draft post won't show on calendar

    NewsletterSend::factory()->forPost($post)->scheduled()->create([
        'subject_line' => 'Weekly Newsletter',
        'scheduled_at' => now()->startOfMonth()->addDays(20),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 1)
        ->where('events.0.type', 'newsletter')
        ->where('events.0.title', 'Weekly Newsletter')
    );
});

test('calendar shows sent newsletter sends as events', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->draft()->create(); // Draft post won't show on calendar

    NewsletterSend::factory()->forPost($post)->sent()->create([
        'subject_line' => 'Sent Newsletter',
        'sent_at' => now()->startOfMonth()->addDays(3),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 1)
        ->where('events.0.type', 'newsletter')
        ->where('events.0.title', 'Sent Newsletter')
    );
});

test('calendar only shows posts for current brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    Post::factory()->forBrand($brand)->scheduled()->create([
        'title' => 'My Brand Post',
        'scheduled_at' => now()->startOfMonth()->addDays(10),
    ]);

    // Create another brand with a post that should not appear
    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    Post::factory()->forBrand($otherBrand)->scheduled()->create([
        'title' => 'Other Brand Post',
        'scheduled_at' => now()->startOfMonth()->addDays(10),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 1)
        ->where('events.0.title', 'My Brand Post')
    );
});

test('calendar excludes draft posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    Post::factory()->forBrand($brand)->draft()->create([
        'title' => 'Draft Post',
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 0)
    );
});

test('calendar excludes draft social posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    SocialPost::factory()->forBrand($brand)->draft()->create();

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 0)
    );
});

test('calendar excludes posts outside current month', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    // Post in current month
    Post::factory()->forBrand($brand)->scheduled()->create([
        'title' => 'Current Month Post',
        'scheduled_at' => now()->startOfMonth()->addDays(5),
    ]);

    // Post in next month
    Post::factory()->forBrand($brand)->scheduled()->create([
        'title' => 'Next Month Post',
        'scheduled_at' => now()->addMonth()->startOfMonth()->addDays(5),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 1)
        ->where('events.0.title', 'Current Month Post')
    );
});

test('calendar shows multiple event types', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $post = Post::factory()->forBrand($brand)->scheduled()->create([
        'scheduled_at' => now()->startOfMonth()->addDays(5),
    ]);

    SocialPost::factory()->forBrand($brand)->scheduled()->create([
        'scheduled_at' => now()->startOfMonth()->addDays(10),
    ]);

    NewsletterSend::factory()->forPost($post)->scheduled()->create([
        'scheduled_at' => now()->startOfMonth()->addDays(15),
    ]);

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('events', 3)
    );
});
