<?php

use App\Jobs\SendNewsletterToSubscriber;
use App\Mail\NewsletterMail;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\SentMessage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
    $this->post = Post::factory()->forBrand($this->brand)->create();
    $this->subscriber = Subscriber::factory()->forBrand($this->brand)->confirmed()->create();
    $this->newsletterSend = NewsletterSend::factory()
        ->forPost($this->post)
        ->forBrand($this->brand)
        ->create();
});

test('it sends newsletter email to subscriber', function () {
    Mail::fake();

    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);
    $job->handle();

    Mail::assertSent(NewsletterMail::class, function ($mail) {
        return $mail->hasTo($this->subscriber->email);
    });
});

test('it increments sent count on success', function () {
    Mail::fake();

    $initialCount = $this->newsletterSend->sent_count;

    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);
    $job->handle();

    $this->newsletterSend->refresh();

    expect($this->newsletterSend->sent_count)->toBe($initialCount + 1);
});

test('it creates email event on success', function () {
    // Use a fake mailer that returns a message with ID
    $sentMessage = Mockery::mock(SentMessage::class);
    $sentMessage->shouldReceive('getMessageId')
        ->andReturn('test-message-id-123');

    Mail::shouldReceive('to')
        ->once()
        ->with($this->subscriber->email)
        ->andReturnSelf();
    Mail::shouldReceive('send')
        ->once()
        ->andReturn($sentMessage);

    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);
    $job->handle();

    $this->assertDatabaseHas('email_events', [
        'subscriber_id' => $this->subscriber->id,
        'newsletter_send_id' => $this->newsletterSend->id,
        'ses_message_id' => 'test-message-id-123',
        'event_type' => 'sent',
    ]);
});

test('it increments failed count on exception', function () {
    Mail::shouldReceive('to')
        ->once()
        ->andReturnSelf();
    Mail::shouldReceive('send')
        ->once()
        ->andThrow(new \Exception('SMTP connection failed'));

    $initialCount = $this->newsletterSend->failed_count;

    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);

    try {
        $job->handle();
    } catch (\Exception $e) {
        // Expected
    }

    $this->newsletterSend->refresh();

    expect($this->newsletterSend->failed_count)->toBe($initialCount + 1);
});

test('it rethrows exception after incrementing failed count', function () {
    Mail::shouldReceive('to')
        ->once()
        ->andReturnSelf();
    Mail::shouldReceive('send')
        ->once()
        ->andThrow(new \Exception('Connection failed'));

    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);

    expect(fn () => $job->handle())->toThrow(\Exception::class, 'Connection failed');
});

test('it skips sending when batch is cancelled', function () {
    Mail::fake();

    $batch = Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->once()->andReturn(true);

    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);

    // Use reflection to set the batch
    $reflection = new \ReflectionClass($job);
    $property = $reflection->getProperty('batchId');
    $property->setAccessible(true);
    $property->setValue($job, 'test-batch-id');

    $this->mock(\Illuminate\Bus\BatchRepository::class, function (MockInterface $mock) use ($batch) {
        $mock->shouldReceive('find')
            ->with('test-batch-id')
            ->andReturn($batch);
    });

    $job->handle();

    Mail::assertNothingSent();
});

test('it has rate limiting middleware', function () {
    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);

    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(\Illuminate\Queue\Middleware\RateLimited::class);
});

test('it configures tries and backoff', function () {
    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe(60);
});

test('it handles null message id', function () {
    Mail::shouldReceive('to')
        ->once()
        ->with($this->subscriber->email)
        ->andReturnSelf();
    Mail::shouldReceive('send')
        ->once()
        ->andReturn(null);

    $job = new SendNewsletterToSubscriber($this->newsletterSend, $this->subscriber);
    $job->handle();

    // Should not create email event without message ID
    $this->assertDatabaseMissing('email_events', [
        'subscriber_id' => $this->subscriber->id,
        'newsletter_send_id' => $this->newsletterSend->id,
    ]);

    // But should still increment sent count
    $this->newsletterSend->refresh();
    expect($this->newsletterSend->sent_count)->toBeGreaterThan(0);
});
