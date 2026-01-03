<?php

use App\Enums\SubscriberStatus;
use App\Models\Brand;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('visitors can subscribe to a brand newsletter', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);

    $response = $this->postJson("/@{$brand->slug}/subscribe", [
        'email' => 'subscriber@example.com',
        'name' => 'John Doe',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Please check your email to confirm your subscription.']);
    $this->assertDatabaseHas('subscribers', [
        'brand_id' => $brand->id,
        'email' => 'subscriber@example.com',
    ]);
});

test('email is required for subscription', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);

    $response = $this->postJson("/@{$brand->slug}/subscribe", [
        'name' => 'John',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

test('email must be valid for subscription', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);

    $response = $this->postJson("/@{$brand->slug}/subscribe", [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

test('duplicate email shows already subscribed message', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    Subscriber::factory()->forBrand($brand)->confirmed()->create(['email' => 'existing@example.com']);

    $response = $this->postJson("/@{$brand->slug}/subscribe", [
        'email' => 'existing@example.com',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'You are already subscribed.']);
    expect(Subscriber::where('brand_id', $brand->id)->where('email', 'existing@example.com')->count())->toBe(1);
});

test('subscribers can confirm their subscription', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    $subscriber = Subscriber::factory()->forBrand($brand)->pending()->create([
        'confirmation_token' => 'test-token-123',
    ]);

    $response = $this->get("/@{$brand->slug}/confirm/test-token-123");

    $response->assertRedirect();
    $subscriber->refresh();
    expect($subscriber->status)->toBe(SubscriberStatus::Confirmed);
    expect($subscriber->confirmed_at)->not->toBeNull();
});

test('invalid confirmation token redirects with error', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);

    $response = $this->get("/@{$brand->slug}/confirm/invalid-token");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Invalid confirmation link.');
});

test('subscribers can unsubscribe using their token', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);
    $subscriber = Subscriber::factory()->forBrand($brand)->confirmed()->create([
        'unsubscribe_token' => 'unsubscribe-token-123',
    ]);

    $response = $this->get("/@{$brand->slug}/unsubscribe/unsubscribe-token-123");

    $response->assertRedirect();
    $subscriber->refresh();
    expect($subscriber->status)->toBe(SubscriberStatus::Unsubscribed);
    expect($subscriber->unsubscribed_at)->not->toBeNull();
});

test('invalid unsubscribe token redirects with error', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);

    $response = $this->get("/@{$brand->slug}/unsubscribe/invalid-token");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Invalid unsubscribe link.');
});
