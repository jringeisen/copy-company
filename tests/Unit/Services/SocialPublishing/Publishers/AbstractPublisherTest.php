<?php

use App\Models\Brand;
use App\Models\Media;
use App\Models\User;
use App\Services\SocialPublishing\Publishers\FacebookPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->publisher = new FacebookPublisher; // Use Facebook as concrete implementation
});

test('hasRequiredFields returns true when all fields present', function () {
    $credentials = ['access_token' => 'token', 'page_id' => '123'];

    expect($this->publisher->validateCredentials($credentials))->toBeTrue();
});

test('hasRequiredFields returns false when field is missing', function () {
    $credentials = ['access_token' => 'token'];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('hasRequiredFields returns false when field is empty', function () {
    $credentials = ['access_token' => 'token', 'page_id' => ''];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('isTokenExpired returns false when no expires_at', function () {
    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('isTokenExpired');
    $method->setAccessible(true);

    $credentials = ['access_token' => 'token'];
    expect($method->invoke($this->publisher, $credentials))->toBeFalse();
});

test('isTokenExpired returns true when token is expired', function () {
    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('isTokenExpired');
    $method->setAccessible(true);

    $credentials = ['expires_at' => now()->subHour()->toDateTimeString()];
    expect($method->invoke($this->publisher, $credentials))->toBeTrue();
});

test('isTokenExpired returns false when token is not expired', function () {
    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('isTokenExpired');
    $method->setAccessible(true);

    $credentials = ['expires_at' => now()->addHour()->toDateTimeString()];
    expect($method->invoke($this->publisher, $credentials))->toBeFalse();
});

test('logError logs error message with platform name', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('[facebookPublisher] Test error', ['context' => 'value']);

    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('logError');
    $method->setAccessible(true);

    $method->invoke($this->publisher, 'Test error', ['context' => 'value']);
});

test('logSuccess logs success message', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('[facebookPublisher] Published successfully', ['external_id' => 'post_123']);

    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('logSuccess');
    $method->setAccessible(true);

    $method->invoke($this->publisher, 'post_123');
});

test('successResponse returns correct structure', function () {
    Log::shouldReceive('info')->once();

    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('successResponse');
    $method->setAccessible(true);

    $result = $method->invoke($this->publisher, 'post_123');

    expect($result)->toBe([
        'success' => true,
        'external_id' => 'post_123',
        'error' => null,
    ]);
});

test('errorResponse returns correct structure', function () {
    Log::shouldReceive('error')->once();

    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('errorResponse');
    $method->setAccessible(true);

    $result = $method->invoke($this->publisher, 'Something went wrong');

    expect($result)->toBe([
        'success' => false,
        'external_id' => null,
        'error' => 'Something went wrong',
    ]);
});

test('getMediaUrl returns null when media not found', function () {
    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('getMediaUrl');
    $method->setAccessible(true);

    $result = $method->invoke($this->publisher, 99999);

    expect($result)->toBeNull();
});

test('getMediaUrl returns temporary url for existing media', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->create([
        'disk' => 'public',
        'path' => 'media/test-image.jpg',
    ]);

    // Create fake file
    Storage::disk('public')->put('media/test-image.jpg', 'fake content');

    $reflection = new ReflectionClass($this->publisher);
    $method = $reflection->getMethod('getMediaUrl');
    $method->setAccessible(true);

    $result = $method->invoke($this->publisher, $media->id);

    expect($result)->toContain('test-image.jpg');
});
