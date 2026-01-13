<?php

use App\Enums\NewsletterSendStatus;
use App\Enums\PostStatus;
use App\Jobs\PublishScheduledPosts;
use App\Jobs\SendNewsletterToSubscriber;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('it publishes scheduled posts that are due', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ]);

    (new PublishScheduledPosts)->handle();

    $post->refresh();

    expect($post->status)->toBe(PostStatus::Published)
        ->and($post->published_at)->not->toBeNull();
});

test('it does not publish posts scheduled for the future', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->addHour(),
    ]);

    (new PublishScheduledPosts)->handle();

    $post->refresh();

    expect($post->status)->toBe(PostStatus::Scheduled)
        ->and($post->published_at)->toBeNull();
});

test('it does not publish non-scheduled posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $draftPost = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Draft,
        'scheduled_at' => now()->subMinute(),
    ]);
    $publishedPost = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Published,
        'scheduled_at' => now()->subMinute(),
    ]);

    (new PublishScheduledPosts)->handle();

    $draftPost->refresh();
    $publishedPost->refresh();

    expect($draftPost->status)->toBe(PostStatus::Draft)
        ->and($publishedPost->status)->toBe(PostStatus::Published);
});

test('it dispatches newsletter when post is configured as newsletter', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Subscriber::factory()->forBrand($brand)->confirmed()->count(3)->create();

    $post = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
        'send_as_newsletter' => true,
    ]);

    $newsletterSend = NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Draft,
        ]);

    (new PublishScheduledPosts)->handle();

    $newsletterSend->refresh();

    expect($newsletterSend->status)->toBe(NewsletterSendStatus::Sending);
    Queue::assertPushed(SendNewsletterToSubscriber::class, 3);
});

test('it does not dispatch newsletter when already sent', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Subscriber::factory()->forBrand($brand)->confirmed()->create();

    $post = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
        'send_as_newsletter' => true,
    ]);

    NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Sent,
        ]);

    (new PublishScheduledPosts)->handle();

    Queue::assertNothingPushed();
});

test('it marks newsletter as sent when no subscribers', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $post = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
        'send_as_newsletter' => true,
    ]);

    $newsletterSend = NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Draft,
        ]);

    (new PublishScheduledPosts)->handle();

    $newsletterSend->refresh();

    expect($newsletterSend->status)->toBe(NewsletterSendStatus::Sent);
});

test('it publishes multiple due posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $posts = Post::factory()->forBrand($brand)->count(5)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinutes(10),
    ]);

    (new PublishScheduledPosts)->handle();

    foreach ($posts as $post) {
        $post->refresh();
        expect($post->status)->toBe(PostStatus::Published);
    }
});

test('it continues publishing other posts when one fails', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $post1 = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ]);

    $post2 = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ]);

    // Both posts should be published - the job catches exceptions per post
    (new PublishScheduledPosts)->handle();

    $post1->refresh();
    $post2->refresh();

    expect($post1->status)->toBe(PostStatus::Published)
        ->and($post2->status)->toBe(PostStatus::Published);
});

test('it does not dispatch newsletter when send_as_newsletter is false', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Subscriber::factory()->forBrand($brand)->confirmed()->create();

    $post = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
        'send_as_newsletter' => false,
    ]);

    NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Draft,
        ]);

    (new PublishScheduledPosts)->handle();

    Queue::assertNothingPushed();
});

test('it handles posts with no newsletter send record', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $post = Post::factory()->forBrand($brand)->create([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
        'send_as_newsletter' => true,
    ]);

    // No newsletter send record created
    (new PublishScheduledPosts)->handle();

    $post->refresh();

    expect($post->status)->toBe(PostStatus::Published);
});
