<?php

use App\Mail\SubscriptionConfirmation;
use App\Models\Brand;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create([
        'name' => 'My Newsletter',
        'slug' => 'my-newsletter',
    ]);
    $this->subscriber = Subscriber::factory()->forBrand($this->brand)->create([
        'email' => 'new-subscriber@example.com',
        'confirmation_token' => 'confirm-token-123',
    ]);
});

test('it sets correct subject', function () {
    $mail = new SubscriptionConfirmation($this->subscriber, $this->brand);

    $envelope = $mail->envelope();

    expect($envelope->subject)->toBe('Confirm your subscription to My Newsletter');
});

test('it includes confirm url with token', function () {
    $mail = new SubscriptionConfirmation($this->subscriber, $this->brand);

    $content = $mail->content();

    $expectedUrl = route('public.subscribe.confirm', [
        'brand' => $this->brand,
        'token' => 'confirm-token-123',
    ]);

    expect($content->with['confirmUrl'])->toBe($expectedUrl);
});

test('it includes brand name', function () {
    $mail = new SubscriptionConfirmation($this->subscriber, $this->brand);

    $content = $mail->content();

    expect($content->with['brandName'])->toBe('My Newsletter');
});

test('it uses markdown template', function () {
    $mail = new SubscriptionConfirmation($this->subscriber, $this->brand);

    $content = $mail->content();

    expect($content->markdown)->toBe('emails.subscription-confirmation');
});

test('it has no attachments', function () {
    $mail = new SubscriptionConfirmation($this->subscriber, $this->brand);

    $attachments = $mail->attachments();

    expect($attachments)->toBeEmpty();
});

test('it can be rendered', function () {
    $mail = new SubscriptionConfirmation($this->subscriber, $this->brand);

    // Ensure it renders without error
    $mail->render();

    expect(true)->toBeTrue();
});

test('it works with different brand names', function () {
    $brand = Brand::factory()->forUser($this->user)->create([
        'name' => 'Tech Weekly',
    ]);

    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    $mail = new SubscriptionConfirmation($subscriber, $brand);

    $envelope = $mail->envelope();

    expect($envelope->subject)->toBe('Confirm your subscription to Tech Weekly');
});
