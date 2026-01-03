<?php

use App\Enums\SubscriberStatus;
use App\Http\Resources\SubscriberResource;
use App\Models\Brand;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('subscriber resource transforms subscriber correctly', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'source' => 'website',
    ]);

    $resource = new SubscriberResource($subscriber);
    $array = $resource->toArray(app(Request::class));

    expect($array)->toHaveKey('id', $subscriber->id)
        ->toHaveKey('email', 'test@example.com')
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('first_name', 'John')
        ->toHaveKey('last_name', 'Doe')
        ->toHaveKey('source', 'website');
});

test('subscriber resource includes full name attribute', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);

    $resource = new SubscriberResource($subscriber);
    $array = $resource->toArray(app(Request::class));

    expect($array['full_name'])->toBe('Jane Smith');
});

test('subscriber resource converts status to value', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->confirmed()->create();

    $resource = new SubscriberResource($subscriber);
    $array = $resource->toArray(app(Request::class));

    expect($array['status'])->toBe(SubscriberStatus::Confirmed->value);
});

test('subscriber resource formats dates correctly', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->confirmed()->create([
        'confirmed_at' => now(),
        'subscribed_at' => now(),
    ]);

    $resource = new SubscriberResource($subscriber);
    $array = $resource->toArray(app(Request::class));

    expect($array['confirmed_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
    expect($array['subscribed_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
    expect($array['created_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
});

test('subscriber resource includes brand when loaded', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create(['name' => 'Test Brand']);
    $subscriber = Subscriber::factory()->forBrand($brand)->create();
    $subscriber->load('brand');

    $resource = new SubscriberResource($subscriber);
    $array = $resource->toArray(app(Request::class));

    expect($array['brand'])->toBeInstanceOf(\Illuminate\Http\Resources\Json\JsonResource::class);
});

test('subscriber resource handles null dates', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->pending()->create([
        'unsubscribed_at' => null,
    ]);

    $resource = new SubscriberResource($subscriber);
    $array = $resource->toArray(app(Request::class));

    expect($array['confirmed_at'])->toBeNull();
    expect($array['unsubscribed_at'])->toBeNull();
    // subscribed_at uses useCurrent() default so it's never null
    expect($array['subscribed_at'])->not->toBeNull();
});
