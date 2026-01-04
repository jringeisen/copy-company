<?php

use App\Enums\NewsletterSendStatus;
use App\Jobs\ProcessNewsletterSend;
use App\Jobs\SendNewsletterToSubscriber;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('process newsletter send dispatches jobs for confirmed subscribers', function () {
    Bus::fake([SendNewsletterToSubscriber::class]);

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    // Create confirmed subscribers
    Subscriber::factory()->forBrand($brand)->confirmed()->count(3)->create();

    // Create non-confirmed subscribers (should be skipped)
    Subscriber::factory()->forBrand($brand)->pending()->create();
    Subscriber::factory()->forBrand($brand)->unsubscribed()->create();

    $newsletterSend = NewsletterSend::create([
        'post_id' => $post->id,
        'brand_id' => $brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Scheduled,
        'scheduled_at' => now(),
    ]);

    ProcessNewsletterSend::dispatchSync($newsletterSend);

    $newsletterSend->refresh();

    expect($newsletterSend->status)->toBe(NewsletterSendStatus::Sending);
    expect($newsletterSend->total_recipients)->toBe(3);
    expect($newsletterSend->batch_id)->not->toBeNull();
});

test('process newsletter send marks as sent when no subscribers', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    $newsletterSend = NewsletterSend::create([
        'post_id' => $post->id,
        'brand_id' => $brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Scheduled,
        'scheduled_at' => now(),
    ]);

    ProcessNewsletterSend::dispatchSync($newsletterSend);

    $newsletterSend->refresh();

    expect($newsletterSend->status)->toBe(NewsletterSendStatus::Sent);
    expect($newsletterSend->total_recipients)->toBe(0);
    expect($newsletterSend->sent_at)->not->toBeNull();
});

test('process newsletter send skips if already sending', function () {
    Bus::fake([SendNewsletterToSubscriber::class]);

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    Subscriber::factory()->forBrand($brand)->confirmed()->count(3)->create();

    $newsletterSend = NewsletterSend::create([
        'post_id' => $post->id,
        'brand_id' => $brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Sending,
        'scheduled_at' => now(),
    ]);

    ProcessNewsletterSend::dispatchSync($newsletterSend);

    Bus::assertNothingDispatched();
});

test('process newsletter send skips if already sent', function () {
    Bus::fake([SendNewsletterToSubscriber::class]);

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    $newsletterSend = NewsletterSend::create([
        'post_id' => $post->id,
        'brand_id' => $brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Sent,
        'scheduled_at' => now(),
        'sent_at' => now(),
    ]);

    ProcessNewsletterSend::dispatchSync($newsletterSend);

    Bus::assertNothingDispatched();
});

test('publishing post with newsletter dispatches process job', function () {
    Queue::fake([ProcessNewsletterSend::class]);

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->draft()->create();

    $this->actingAs($user)->post(route('posts.publish', $post), [
        'schedule_mode' => 'now',
        'publish_to_blog' => true,
        'send_as_newsletter' => true,
        'subject_line' => 'My Newsletter Subject',
        'preview_text' => 'Preview text here',
    ]);

    Queue::assertPushed(ProcessNewsletterSend::class);

    $this->assertDatabaseHas('newsletter_sends', [
        'post_id' => $post->id,
        'subject_line' => 'My Newsletter Subject',
        'preview_text' => 'Preview text here',
    ]);
});
