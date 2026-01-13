<?php

use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\SocialPublishing\Publishers\TikTokPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->publisher = new TikTokPublisher;
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('getPlatform returns tiktok', function () {
    expect($this->publisher->getPlatform())->toBe('tiktok');
});

test('getRequiredScopes returns correct scopes', function () {
    expect($this->publisher->getRequiredScopes())
        ->toContain('video.upload')
        ->toContain('video.publish');
});

test('validateCredentials returns true with access_token', function () {
    $credentials = ['access_token' => 'token'];

    expect($this->publisher->validateCredentials($credentials))->toBeTrue();
});

test('validateCredentials returns false without access_token', function () {
    $credentials = [];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('publish fails without media', function () {
    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => [],
    ]);

    $credentials = ['access_token' => 'token'];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('TikTok requires video content to publish.');
});

test('publish fails when video file not found', function () {
    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => ['nonexistent-video.mp4'],
    ]);

    $credentials = ['access_token' => 'token'];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Video file not found.');
});

test('publish initializes upload successfully', function () {
    Http::fake([
        'open.tiktokapis.com/*' => Http::response([
            'data' => ['publish_id' => 'upload_123'],
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    // TikTok uses storage_path('app/public/...') to check file existence
    $videoPath = storage_path('app/public/test-video.mp4');
    @mkdir(dirname($videoPath), 0755, true);
    file_put_contents($videoPath, 'fake video content');

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'Test TikTok video',
        'media' => ['test-video.mp4'],
    ]);

    $credentials = ['access_token' => 'token'];

    $result = $this->publisher->publish($socialPost, $credentials);

    @unlink($videoPath);

    expect($result['success'])->toBeTrue()
        ->and($result['external_id'])->toBe('upload_123');
});

test('publish handles api error', function () {
    Http::fake([
        'open.tiktokapis.com/*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    Log::shouldReceive('error')->once();

    $videoPath = storage_path('app/public/test-video.mp4');
    @mkdir(dirname($videoPath), 0755, true);
    file_put_contents($videoPath, 'fake video content');

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => ['test-video.mp4'],
    ]);

    $credentials = ['access_token' => 'invalid_token'];

    $result = $this->publisher->publish($socialPost, $credentials);

    @unlink($videoPath);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Failed to initialize TikTok upload');
});

test('publish handles exception', function () {
    Http::fake(function () {
        throw new Exception('Network error');
    });

    Log::shouldReceive('error')->once();

    $videoPath = storage_path('app/public/test-video.mp4');
    @mkdir(dirname($videoPath), 0755, true);
    file_put_contents($videoPath, 'fake video content');

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => ['test-video.mp4'],
    ]);

    $credentials = ['access_token' => 'token'];

    $result = $this->publisher->publish($socialPost, $credentials);

    @unlink($videoPath);

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
        'open.tiktokapis.com/v2/oauth/token*' => Http::response([
            'access_token' => 'new_token',
            'refresh_token' => 'new_refresh',
            'expires_in' => 86400,
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
        'open.tiktokapis.com/v2/oauth/token*' => Http::response([
            'access_token' => 'new_token',
            'expires_in' => 86400,
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
        'open.tiktokapis.com/v2/oauth/token*' => Http::response(['error' => 'Invalid token'], 400),
    ]);

    $credentials = [
        'access_token' => 'invalid',
        'refresh_token' => 'invalid',
    ];

    expect(fn () => $this->publisher->refreshToken($credentials))
        ->toThrow(Exception::class, 'Failed to refresh TikTok token');
});
