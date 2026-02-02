<?php

use App\Enums\NewsletterSendStatus;
use App\Enums\PostStatus;
use App\Enums\SocialPostStatus;
use App\Models\Loop;
use App\Models\LoopItem;
use App\Models\LoopSchedule;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('posts:publish-scheduled is scheduled with when condition', function () {
    $this->assertFalse(
        Post::where('status', PostStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->exists()
    );
});

test('posts:publish-scheduled runs when scheduled posts are due', function () {
    Post::factory()->scheduled()->create(['scheduled_at' => now()->subMinute()]);

    $this->assertTrue(
        Post::where('status', PostStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->exists()
    );
});

test('posts:publish-scheduled does not run when scheduled posts are in the future', function () {
    Post::factory()->scheduled()->create(['scheduled_at' => now()->addHour()]);

    $this->assertFalse(
        Post::where('status', PostStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->exists()
    );
});

test('newsletters:process-scheduled runs when scheduled newsletters are due', function () {
    NewsletterSend::factory()->scheduled()->create(['scheduled_at' => now()->subMinute()]);

    $this->assertTrue(
        NewsletterSend::where('status', NewsletterSendStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->exists()
    );
});

test('newsletters:process-scheduled does not run when no scheduled newsletters exist', function () {
    NewsletterSend::factory()->draft()->create();

    $this->assertFalse(
        NewsletterSend::where('status', NewsletterSendStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->exists()
    );
});

test('social posts job runs when scheduled social posts are due', function () {
    SocialPost::factory()->scheduled()->create(['scheduled_at' => now()->subMinute()]);

    $this->assertTrue(
        SocialPost::where('status', SocialPostStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->exists()
    );
});

test('social posts job does not run when no scheduled social posts exist', function () {
    SocialPost::factory()->draft()->create();

    $this->assertFalse(
        SocialPost::where('status', SocialPostStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->exists()
    );
});

test('loops job runs when active loops with schedules and items exist', function () {
    $loop = Loop::factory()->create(['is_active' => true]);
    LoopSchedule::factory()->for($loop)->create();
    LoopItem::factory()->for($loop)->create();

    $this->assertTrue(
        Loop::where('is_active', true)
            ->whereHas('schedules')
            ->whereHas('items')
            ->exists()
    );
});

test('loops job does not run when no active loops exist', function () {
    Loop::factory()->paused()->create();

    $this->assertFalse(
        Loop::where('is_active', true)
            ->whereHas('schedules')
            ->whereHas('items')
            ->exists()
    );
});

test('loops job does not run when active loop has no schedules', function () {
    $loop = Loop::factory()->create(['is_active' => true]);
    LoopItem::factory()->for($loop)->create();

    $this->assertFalse(
        Loop::where('is_active', true)
            ->whereHas('schedules')
            ->whereHas('items')
            ->exists()
    );
});

test('loops job does not run when active loop has no items', function () {
    $loop = Loop::factory()->create(['is_active' => true]);
    LoopSchedule::factory()->for($loop)->create();

    $this->assertFalse(
        Loop::where('is_active', true)
            ->whereHas('schedules')
            ->whereHas('items')
            ->exists()
    );
});
