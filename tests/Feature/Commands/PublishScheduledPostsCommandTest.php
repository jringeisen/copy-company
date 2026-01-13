<?php

use App\Jobs\PublishScheduledPosts;
use Illuminate\Support\Facades\Queue;

test('publish scheduled posts command dispatches job', function () {
    Queue::fake();

    $this->artisan('posts:publish-scheduled')
        ->expectsOutput('Checking for scheduled posts to publish...')
        ->expectsOutput('Scheduled posts job dispatched.')
        ->assertExitCode(0);

    Queue::assertPushed(PublishScheduledPosts::class);
});

test('publish scheduled posts command returns success', function () {
    Queue::fake();

    $this->artisan('posts:publish-scheduled')
        ->assertSuccessful();
});
