<?php

use App\Services\SocialPublishing\InstagramAccountsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->service = new InstagramAccountsService;
});

test('fetchInstagramAccounts returns accounts on successful response', function () {
    Http::fake([
        'graph.facebook.com/v18.0/me/accounts*' => Http::response([
            'data' => [
                [
                    'id' => 'page_123',
                    'name' => 'Test Page',
                    'access_token' => 'page_token_123',
                    'instagram_business_account' => [
                        'id' => 'ig_123',
                    ],
                ],
            ],
        ], 200),
        'graph.facebook.com/v18.0/ig_123*' => Http::response([
            'id' => 'ig_123',
            'username' => 'test_instagram',
            'name' => 'Test Instagram Account',
            'profile_picture_url' => 'https://example.com/pic.jpg',
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $accounts = $this->service->fetchInstagramAccounts('user_access_token');

    expect($accounts)->toHaveCount(1)
        ->and($accounts[0]['id'])->toBe('ig_123')
        ->and($accounts[0]['username'])->toBe('test_instagram')
        ->and($accounts[0]['name'])->toBe('Test Instagram Account')
        ->and($accounts[0]['page_id'])->toBe('page_123')
        ->and($accounts[0]['page_name'])->toBe('Test Page')
        ->and($accounts[0]['access_token'])->toBe('page_token_123');
});

test('fetchInstagramAccounts skips pages without instagram business account', function () {
    Http::fake([
        'graph.facebook.com/v18.0/me/accounts*' => Http::response([
            'data' => [
                [
                    'id' => 'page_without_ig',
                    'name' => 'Page Without Instagram',
                    'access_token' => 'page_token',
                    // No instagram_business_account
                ],
                [
                    'id' => 'page_with_empty_ig',
                    'name' => 'Page With Empty Instagram',
                    'access_token' => 'page_token_2',
                    'instagram_business_account' => [],
                ],
            ],
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $accounts = $this->service->fetchInstagramAccounts('user_access_token');

    expect($accounts)->toBe([]);
});

test('fetchInstagramAccounts returns empty array on API error', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    Log::shouldReceive('error')->once();

    $accounts = $this->service->fetchInstagramAccounts('invalid_token');

    expect($accounts)->toBe([]);
});

test('fetchInstagramAccounts returns empty array on exception', function () {
    Http::fake(function () {
        throw new Exception('Network error');
    });

    Log::shouldReceive('error')->once();

    $accounts = $this->service->fetchInstagramAccounts('user_access_token');

    expect($accounts)->toBe([]);
});

test('fetchInstagramAccounts returns empty array when no pages exist', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $accounts = $this->service->fetchInstagramAccounts('user_access_token');

    expect($accounts)->toBe([]);
});

test('fetchInstagramAccounts handles multiple instagram accounts', function () {
    Http::fake([
        'graph.facebook.com/v18.0/me/accounts*' => Http::response([
            'data' => [
                [
                    'id' => 'page_1',
                    'name' => 'Page 1',
                    'access_token' => 'token_1',
                    'instagram_business_account' => ['id' => 'ig_1'],
                ],
                [
                    'id' => 'page_2',
                    'name' => 'Page 2',
                    'access_token' => 'token_2',
                    'instagram_business_account' => ['id' => 'ig_2'],
                ],
            ],
        ], 200),
        'graph.facebook.com/v18.0/ig_1*' => Http::response([
            'id' => 'ig_1',
            'username' => 'instagram_1',
            'name' => 'Instagram 1',
        ], 200),
        'graph.facebook.com/v18.0/ig_2*' => Http::response([
            'id' => 'ig_2',
            'username' => 'instagram_2',
            'name' => 'Instagram 2',
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $accounts = $this->service->fetchInstagramAccounts('user_access_token');

    expect($accounts)->toHaveCount(2)
        ->and($accounts[0]['id'])->toBe('ig_1')
        ->and($accounts[1]['id'])->toBe('ig_2');
});

test('fetchInstagramAccounts uses username when name is not provided', function () {
    Http::fake([
        'graph.facebook.com/v18.0/me/accounts*' => Http::response([
            'data' => [
                [
                    'id' => 'page_123',
                    'name' => 'Test Page',
                    'access_token' => 'page_token_123',
                    'instagram_business_account' => ['id' => 'ig_123'],
                ],
            ],
        ], 200),
        'graph.facebook.com/v18.0/ig_123*' => Http::response([
            'id' => 'ig_123',
            'username' => 'test_username',
            // No 'name' field
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $accounts = $this->service->fetchInstagramAccounts('user_access_token');

    expect($accounts[0]['name'])->toBe('test_username');
});

test('fetchInstagramAccounts handles missing profile picture', function () {
    Http::fake([
        'graph.facebook.com/v18.0/me/accounts*' => Http::response([
            'data' => [
                [
                    'id' => 'page_123',
                    'name' => 'Test Page',
                    'access_token' => 'page_token_123',
                    'instagram_business_account' => ['id' => 'ig_123'],
                ],
            ],
        ], 200),
        'graph.facebook.com/v18.0/ig_123*' => Http::response([
            'id' => 'ig_123',
            'username' => 'test_username',
            'name' => 'Test Name',
            // No profile_picture_url
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $accounts = $this->service->fetchInstagramAccounts('user_access_token');

    expect($accounts[0]['profile_picture_url'])->toBeNull();
});

test('fetchInstagramAccounts skips instagram accounts with failed detail fetch', function () {
    Http::fake([
        'graph.facebook.com/v18.0/me/accounts*' => Http::response([
            'data' => [
                [
                    'id' => 'page_123',
                    'name' => 'Test Page',
                    'access_token' => 'page_token_123',
                    'instagram_business_account' => ['id' => 'ig_123'],
                ],
            ],
        ], 200),
        'graph.facebook.com/v18.0/ig_123*' => Http::response(['error' => 'Not found'], 404),
    ]);

    Log::shouldReceive('info')->once();

    $accounts = $this->service->fetchInstagramAccounts('user_access_token');

    expect($accounts)->toBe([]);
});
