<?php

use App\Enums\NewsletterSendStatus;
use App\Jobs\ProcessNewsletterSend;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('command processes scheduled newsletters due for sending', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    $newsletterSend = NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);

    $this->artisan('newsletters:process-scheduled')
        ->assertExitCode(0);

    Queue::assertPushed(ProcessNewsletterSend::class, function ($job) use ($newsletterSend) {
        return $job->newsletterSend->id === $newsletterSend->id;
    });
});

test('command does not process newsletters scheduled for the future', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Scheduled,
            'scheduled_at' => now()->addHour(),
        ]);

    $this->artisan('newsletters:process-scheduled')
        ->expectsOutput('No scheduled newsletters due for processing.')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

test('command does not process non-scheduled newsletters', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Draft,
            'scheduled_at' => now()->subMinute(),
        ]);

    NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Sending,
            'scheduled_at' => now()->subMinute(),
        ]);

    $this->artisan('newsletters:process-scheduled')
        ->expectsOutput('No scheduled newsletters due for processing.')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

test('command processes multiple due newsletters', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->count(3)
        ->create([
            'status' => NewsletterSendStatus::Scheduled,
            'scheduled_at' => now()->subMinutes(5),
        ]);

    $this->artisan('newsletters:process-scheduled')
        ->expectsOutputToContain('Processing 3 scheduled newsletter(s)...')
        ->assertExitCode(0);

    Queue::assertPushed(ProcessNewsletterSend::class, 3);
});

test('command outputs newsletter details when processing', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    $newsletterSend = NewsletterSend::factory()
        ->forPost($post)
        ->forBrand($brand)
        ->create([
            'status' => NewsletterSendStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
            'subject_line' => 'Test Newsletter Subject',
        ]);

    $this->artisan('newsletters:process-scheduled')
        ->expectsOutputToContain('Test Newsletter Subject')
        ->expectsOutput('Done!')
        ->assertExitCode(0);
});
