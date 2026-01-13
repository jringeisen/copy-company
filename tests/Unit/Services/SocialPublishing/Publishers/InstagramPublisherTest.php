<?php

use App\Models\Brand;
use App\Models\Media;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\SocialPublishing\Publishers\InstagramPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->publisher = new InstagramPublisher;
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('getPlatform returns instagram', function () {
    expect($this->publisher->getPlatform())->toBe('instagram');
});

test('getRequiredScopes returns correct scopes', function () {
    expect($this->publisher->getRequiredScopes())
        ->toContain('instagram_basic')
        ->toContain('instagram_content_publish');
});

test('validateCredentials returns true with required fields', function () {
    $credentials = ['access_token' => 'token', 'instagram_account_id' => '123'];

    expect($this->publisher->validateCredentials($credentials))->toBeTrue();
});

test('validateCredentials returns false without access_token', function () {
    $credentials = ['instagram_account_id' => '123'];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('validateCredentials returns false without instagram_account_id', function () {
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
        'instagram_account_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Instagram requires at least one image to publish.');
});

test('publish fails when media not found', function () {
    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'media' => [99999], // Non-existent media ID
    ]);

    $credentials = [
        'access_token' => 'token',
        'instagram_account_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Could not find the media file to publish.');
});

test('publish creates media container and publishes successfully', function () {
    Http::fake([
        'graph.facebook.com/*/media' => Http::response(['id' => 'container_123'], 200),
        'graph.facebook.com/*/media_publish' => Http::response(['id' => 'post_123'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $media = Media::factory()->forBrand($this->brand)->create([
        'disk' => 'public',
        'path' => 'media/test-image.jpg',
    ]);
    Storage::disk('public')->put('media/test-image.jpg', 'fake content');

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'Test Instagram post',
        'media' => [$media->id],
    ]);

    $credentials = [
        'access_token' => 'token',
        'instagram_account_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeTrue()
        ->and($result['external_id'])->toBe('post_123');
});

test('publish handles container creation failure', function () {
    Http::fake([
        'graph.facebook.com/*/media' => Http::response(['error' => 'Failed'], 400),
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
        'instagram_account_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Failed to create media container');
});

test('publish handles media publish failure', function () {
    Http::fake([
        'graph.facebook.com/*/media' => Http::response(['id' => 'container_123'], 200),
        'graph.facebook.com/*/media_publish' => Http::response(['error' => 'Publish failed'], 400),
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
        'instagram_account_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Failed to publish media');
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
        'instagram_account_id' => '123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Network error');
});
