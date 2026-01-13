<?php

use App\Enums\NewsletterSendStatus;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
    $this->post = Post::factory()->forBrand($this->brand)->create();
});

test('open rate returns 0 when recipients count is 0', function () {
    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Sent,
        'recipients_count' => 0,
        'unique_opens' => 0,
    ]);

    expect($newsletterSend->open_rate)->toBe(0.0);
});

test('open rate calculates correctly with recipients', function () {
    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Sent,
        'recipients_count' => 100,
        'unique_opens' => 25,
    ]);

    expect($newsletterSend->open_rate)->toBe(25.0);
});

test('click rate returns 0 when recipients count is 0', function () {
    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Sent,
        'recipients_count' => 0,
        'unique_clicks' => 0,
    ]);

    expect($newsletterSend->click_rate)->toBe(0.0);
});

test('click rate calculates correctly with recipients', function () {
    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Sent,
        'recipients_count' => 200,
        'unique_clicks' => 10,
    ]);

    expect($newsletterSend->click_rate)->toBe(5.0);
});

test('newsletter send belongs to a post', function () {
    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Scheduled,
    ]);

    expect($newsletterSend->post)->toBeInstanceOf(Post::class);
    expect($newsletterSend->post->id)->toBe($this->post->id);
});

test('newsletter send belongs to a brand', function () {
    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Scheduled,
    ]);

    expect($newsletterSend->brand)->toBeInstanceOf(Brand::class);
    expect($newsletterSend->brand->id)->toBe($this->brand->id);
});

test('newsletter send casts status to enum', function () {
    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Sending,
    ]);

    expect($newsletterSend->status)->toBeInstanceOf(NewsletterSendStatus::class);
    expect($newsletterSend->status)->toBe(NewsletterSendStatus::Sending);
});

test('newsletter send casts scheduled_at to datetime', function () {
    $scheduledAt = now()->addHour();

    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Scheduled,
        'scheduled_at' => $scheduledAt,
    ]);

    expect($newsletterSend->scheduled_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('newsletter send casts sent_at to datetime', function () {
    $sentAt = now();

    $newsletterSend = NewsletterSend::create([
        'post_id' => $this->post->id,
        'brand_id' => $this->brand->id,
        'subject_line' => 'Test Newsletter',
        'status' => NewsletterSendStatus::Sent,
        'sent_at' => $sentAt,
    ]);

    expect($newsletterSend->sent_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
