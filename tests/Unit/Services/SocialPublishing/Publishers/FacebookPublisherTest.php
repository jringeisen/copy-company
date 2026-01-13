<?php

use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\SocialPublishing\Publishers\FacebookPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->publisher = new FacebookPublisher;
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('getPlatform returns facebook', function () {
    expect($this->publisher->getPlatform())->toBe('facebook');
});

test('getRequiredScopes returns correct scopes', function () {
    expect($this->publisher->getRequiredScopes())
        ->toContain('pages_manage_posts')
        ->toContain('pages_read_engagement');
});

test('validateCredentials returns true with required fields', function () {
    $credentials = ['access_token' => 'token', 'page_id' => '123'];

    expect($this->publisher->validateCredentials($credentials))->toBeTrue();
});

test('validateCredentials returns false without access_token', function () {
    $credentials = ['page_id' => '123'];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('validateCredentials returns false without page_id', function () {
    $credentials = ['access_token' => 'token'];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('publish successfully posts to facebook', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['id' => '123456789_987654321'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'Test post content',
        'link' => null,
    ]);

    $credentials = [
        'access_token' => 'user_token',
        'page_id' => '123456789',
        'page_access_token' => 'page_token',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeTrue()
        ->and($result['external_id'])->toBe('123456789_987654321')
        ->and($result['error'])->toBeNull();
});

test('publish includes link when present', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['id' => 'post_id'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'Check out this link',
        'link' => 'https://example.com',
    ]);

    $credentials = [
        'access_token' => 'token',
        'page_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/123/feed')
            && $request['link'] === 'https://example.com';
    });
});

test('publish handles api error response', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Invalid access token'],
        ], 400),
    ]);

    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create();

    $credentials = [
        'access_token' => 'invalid_token',
        'page_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['external_id'])->toBeNull()
        ->and($result['error'])->toBe('Invalid access token');
});

test('publish handles exception', function () {
    Http::fake(function () {
        throw new Exception('Network error');
    });

    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create();

    $credentials = [
        'access_token' => 'token',
        'page_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Network error');
});

test('publish uses page_access_token when available', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['id' => 'post_id'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create();

    $credentials = [
        'access_token' => 'user_token',
        'page_id' => '123',
        'page_access_token' => 'specific_page_token',
    ];

    $this->publisher->publish($socialPost, $credentials);

    Http::assertSent(function ($request) {
        return $request['access_token'] === 'specific_page_token';
    });
});

test('tokenNeedsRefresh returns false when no expires_at', function () {
    $credentials = ['access_token' => 'token'];

    expect($this->publisher->tokenNeedsRefresh($credentials))->toBeFalse();
});

test('tokenNeedsRefresh returns true when token expires within a day', function () {
    $credentials = [
        'access_token' => 'token',
        'expires_at' => now()->addHours(12)->toDateTimeString(),
    ];

    expect($this->publisher->tokenNeedsRefresh($credentials))->toBeTrue();
});

test('tokenNeedsRefresh returns false when token valid for more than a day', function () {
    $credentials = [
        'access_token' => 'token',
        'expires_at' => now()->addDays(30)->toDateTimeString(),
    ];

    expect($this->publisher->tokenNeedsRefresh($credentials))->toBeFalse();
});

test('refreshToken exchanges token successfully', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'access_token' => 'new_long_lived_token',
            'expires_in' => 5184000, // 60 days
        ], 200),
    ]);

    $credentials = ['access_token' => 'short_lived_token'];

    $result = $this->publisher->refreshToken($credentials);

    expect($result['access_token'])->toBe('new_long_lived_token')
        ->and($result['refresh_token'])->toBeNull()
        ->and($result['expires_at'])->not->toBeNull();
});

test('refreshToken throws on api error', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Invalid token'],
        ], 400),
    ]);

    $credentials = ['access_token' => 'invalid_token'];

    expect(fn () => $this->publisher->refreshToken($credentials))
        ->toThrow(RuntimeException::class, 'Failed to refresh Facebook token');
});
