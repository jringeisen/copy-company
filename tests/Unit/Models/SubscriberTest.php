<?php

use App\Enums\SubscriberStatus;
use App\Models\Brand;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('subscriber belongs to a brand', function () {
    $subscriber = Subscriber::factory()->create();

    expect($subscriber->brand)->toBeInstanceOf(Brand::class);
});

test('subscriber generates unsubscribe token on creation', function () {
    $brand = Brand::factory()->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create([
        'unsubscribe_token' => null,
    ]);

    expect($subscriber->unsubscribe_token)->not->toBeNull();
    expect($subscriber->unsubscribe_token)->toHaveLength(64);
});

test('full name returns first and last name', function () {
    $subscriber = Subscriber::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect($subscriber->full_name)->toBe('John Doe');
});

test('full name returns email when no name provided', function () {
    $subscriber = Subscriber::factory()->create([
        'email' => 'test@example.com',
        'first_name' => null,
        'last_name' => null,
    ]);

    expect($subscriber->full_name)->toBe('test@example.com');
});

test('unsubscribe method updates status and timestamp', function () {
    $subscriber = Subscriber::factory()->confirmed()->create();

    $subscriber->unsubscribe();

    expect($subscriber->status)->toBe(SubscriberStatus::Unsubscribed);
    expect($subscriber->unsubscribed_at)->not->toBeNull();
});
