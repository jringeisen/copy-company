<?php

use App\Models\Brand;
use App\Models\Media;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\SocialPublishing\Publishers\PinterestPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->publisher = new PinterestPublisher;
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('getPlatform returns pinterest', function () {
    expect($this->publisher->getPlatform())->toBe('pinterest');
});

test('getRequiredScopes returns correct scopes', function () {
    expect($this->publisher->getRequiredScopes())
        ->toContain('boards:read')
        ->toContain('pins:read')
        ->toContain('pins:write');
});

test('validateCredentials returns true with required fields', function () {
    $credentials = ['access_token' => 'token', 'board_id' => '123'];

    expect($this->publisher->validateCredentials($credentials))->toBeTrue();
});

test('validateCredentials returns false without access_token', function () {
    $credentials = ['board_id' => '123'];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('validateCredentials returns false without board_id', function () {
    $credentials = ['access_token' => 'token'];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('publish fails without media', function () {
    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => [],
    ]);

    $credentials = [
        'access_token' => 'token',
        'board_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Pinterest requires at least one image to create a pin.');
});

test('publish fails when media not found', function () {
    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => [99999],
    ]);

    $credentials = [
        'access_token' => 'token',
        'board_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Could not find the media file to publish.');
});

test('publish creates pin successfully', function () {
    Http::fake([
        'api.pinterest.com/*' => Http::response(['id' => 'pin_123'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $media = Media::factory()->forBrand($this->brand)->create([
        'disk' => 'public',
        'path' => 'media/test-image.jpg',
    ]);
    Storage::disk('public')->put('media/test-image.jpg', 'fake content');

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'Test Pinterest pin',
        'media' => [$media->id],
        'link' => null,
    ]);

    $credentials = [
        'access_token' => 'token',
        'board_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeTrue()
        ->and($result['external_id'])->toBe('pin_123');
});

test('publish includes link when present', function () {
    Http::fake([
        'api.pinterest.com/*' => Http::response(['id' => 'pin_123'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $media = Media::factory()->forBrand($this->brand)->create([
        'disk' => 'public',
        'path' => 'media/test-image.jpg',
    ]);
    Storage::disk('public')->put('media/test-image.jpg', 'fake content');

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'Test pin with link',
        'media' => [$media->id],
        'link' => 'https://example.com',
    ]);

    $credentials = [
        'access_token' => 'token',
        'board_id' => '123',
    ];

    $this->publisher->publish($socialPost, $credentials);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return isset($body['link']) && $body['link'] === 'https://example.com';
    });
});

test('publish handles api error', function () {
    Http::fake([
        'api.pinterest.com/*' => Http::response(['message' => 'Board not found'], 404),
    ]);

    Log::shouldReceive('error')->once();

    $media = Media::factory()->forBrand($this->brand)->create([
        'disk' => 'public',
        'path' => 'media/test-image.jpg',
    ]);
    Storage::disk('public')->put('media/test-image.jpg', 'fake content');

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => [$media->id],
    ]);

    $credentials = [
        'access_token' => 'token',
        'board_id' => 'invalid',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Pinterest API error');
});

test('publish handles exception', function () {
    Http::fake(function () {
        throw new Exception('Network error');
    });

    Log::shouldReceive('error')->once();

    $media = Media::factory()->forBrand($this->brand)->create([
        'disk' => 'public',
        'path' => 'media/test-image.jpg',
    ]);
    Storage::disk('public')->put('media/test-image.jpg', 'fake content');

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => [$media->id],
    ]);

    $credentials = [
        'access_token' => 'token',
        'board_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Network error');
});

test('tokenNeedsRefresh returns false when no expires_at', function () {
    $credentials = ['access_token' => 'token'];

    expect($this->publisher->tokenNeedsRefresh($credentials))->toBeFalse();
});

test('tokenNeedsRefresh returns false when no refresh_token', function () {
    $credentials = [
        'access_token' => 'token',
        'expires_at' => now()->addHours(12)->toDateTimeString(),
    ];

    expect($this->publisher->tokenNeedsRefresh($credentials))->toBeFalse();
});

test('tokenNeedsRefresh returns true when token expires within a day', function () {
    $credentials = [
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'expires_at' => now()->addHours(12)->toDateTimeString(),
    ];

    expect($this->publisher->tokenNeedsRefresh($credentials))->toBeTrue();
});

test('tokenNeedsRefresh returns false when token valid for more than a day', function () {
    $credentials = [
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'expires_at' => now()->addDays(30)->toDateTimeString(),
    ];

    expect($this->publisher->tokenNeedsRefresh($credentials))->toBeFalse();
});

test('refreshToken successfully refreshes token', function () {
    Http::fake([
        'api.pinterest.com/v5/oauth/token' => Http::response([
            'access_token' => 'new_token',
            'refresh_token' => 'new_refresh',
            'expires_in' => 7776000,
        ], 200),
    ]);

    $credentials = [
        'access_token' => 'old_token',
        'refresh_token' => 'old_refresh',
    ];

    $result = $this->publisher->refreshToken($credentials);

    expect($result['access_token'])->toBe('new_token')
        ->and($result['refresh_token'])->toBe('new_refresh')
        ->and($result['expires_at'])->not->toBeNull();
});

test('refreshToken keeps old refresh_token if not returned', function () {
    Http::fake([
        'api.pinterest.com/v5/oauth/token' => Http::response([
            'access_token' => 'new_token',
            'expires_in' => 7776000,
        ], 200),
    ]);

    $credentials = [
        'access_token' => 'old_token',
        'refresh_token' => 'keep_this_refresh',
    ];

    $result = $this->publisher->refreshToken($credentials);

    expect($result['refresh_token'])->toBe('keep_this_refresh');
});

test('refreshToken throws on api error', function () {
    Http::fake([
        'api.pinterest.com/v5/oauth/token' => Http::response(['error' => 'Invalid token'], 400),
    ]);

    $credentials = [
        'access_token' => 'invalid',
        'refresh_token' => 'invalid',
    ];

    expect(fn () => $this->publisher->refreshToken($credentials))
        ->toThrow(Exception::class, 'Failed to refresh Pinterest token');
});
