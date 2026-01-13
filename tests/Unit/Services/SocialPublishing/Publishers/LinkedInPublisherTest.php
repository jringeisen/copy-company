<?php

use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\SocialPublishing\Publishers\LinkedInPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->publisher = new LinkedInPublisher;
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('getPlatform returns linkedin', function () {
    expect($this->publisher->getPlatform())->toBe('linkedin');
});

test('getRequiredScopes returns correct scopes', function () {
    expect($this->publisher->getRequiredScopes())
        ->toContain('w_member_social')
        ->toContain('r_liteprofile');
});

test('validateCredentials returns true with required fields', function () {
    $credentials = ['access_token' => 'token', 'person_id' => 'abc123'];

    expect($this->publisher->validateCredentials($credentials))->toBeTrue();
});

test('validateCredentials returns false without access_token', function () {
    $credentials = ['person_id' => 'abc123'];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('validateCredentials returns false without person_id', function () {
    $credentials = ['access_token' => 'token'];

    expect($this->publisher->validateCredentials($credentials))->toBeFalse();
});

test('publish successfully posts to linkedin', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['id' => 'urn:li:share:123456789'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'Test LinkedIn post',
        'link' => null,
    ]);

    $credentials = [
        'access_token' => 'token',
        'person_id' => 'abc123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeTrue()
        ->and($result['external_id'])->toBe('urn:li:share:123456789');
});

test('publish includes link as article when present', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['id' => 'urn:li:share:123'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'Check out this article',
        'link' => 'https://example.com/article',
    ]);

    $credentials = [
        'access_token' => 'token',
        'person_id' => 'abc123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        $body = $request->data();

        return isset($body['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'])
            && $body['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] === 'ARTICLE'
            && isset($body['specificContent']['com.linkedin.ugc.ShareContent']['media'][0]['originalUrl'])
            && $body['specificContent']['com.linkedin.ugc.ShareContent']['media'][0]['originalUrl'] === 'https://example.com/article';
    });
});

test('publish sends correct payload structure', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['id' => 'urn:li:share:123'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create([
        'content' => 'My professional update',
        'link' => null,
    ]);

    $credentials = [
        'access_token' => 'token',
        'person_id' => 'abc123',
    ];

    $this->publisher->publish($socialPost, $credentials);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['author'] === 'urn:li:person:abc123'
            && $body['lifecycleState'] === 'PUBLISHED'
            && $body['specificContent']['com.linkedin.ugc.ShareContent']['shareCommentary']['text'] === 'My professional update'
            && $body['visibility']['com.linkedin.ugc.MemberNetworkVisibility'] === 'PUBLIC';
    });
});

test('publish handles api error response', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create();

    $credentials = [
        'access_token' => 'invalid_token',
        'person_id' => 'abc123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('LinkedIn API error');
});

test('publish handles exception', function () {
    Http::fake(function () {
        throw new Exception('Connection failed');
    });

    Log::shouldReceive('error')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create();

    $credentials = [
        'access_token' => 'token',
        'person_id' => 'abc123',
    ];

    $result = $this->publisher->publish($socialPost, $credentials);

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toBe('Connection failed');
});

test('publish includes X-Restli-Protocol-Version header', function () {
    Http::fake([
        'api.linkedin.com/*' => Http::response(['id' => 'urn:li:share:123'], 200),
    ]);

    Log::shouldReceive('info')->once();

    $socialPost = SocialPost::factory()->forBrand($this->brand)->create();

    $credentials = [
        'access_token' => 'token',
        'person_id' => 'abc123',
    ];

    $this->publisher->publish($socialPost, $credentials);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Restli-Protocol-Version', '2.0.0');
    });
});
