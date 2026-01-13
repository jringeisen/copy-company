<?php

use App\Services\SocialPublishing\FacebookPagesService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->service = new FacebookPagesService;
});

test('fetchUserPages returns pages on successful response', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [
                [
                    'id' => 'page_123',
                    'name' => 'Test Page',
                    'access_token' => 'page_token_123',
                ],
                [
                    'id' => 'page_456',
                    'name' => 'Another Page',
                    'access_token' => 'page_token_456',
                ],
            ],
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $pages = $this->service->fetchUserPages('user_access_token');

    expect($pages)->toHaveCount(2)
        ->and($pages[0]['id'])->toBe('page_123')
        ->and($pages[0]['name'])->toBe('Test Page')
        ->and($pages[0]['access_token'])->toBe('page_token_123')
        ->and($pages[1]['id'])->toBe('page_456');
});

test('fetchUserPages returns empty array on API error', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    Log::shouldReceive('info')->once();
    Log::shouldReceive('error')->once();

    $pages = $this->service->fetchUserPages('invalid_token');

    expect($pages)->toBe([]);
});

test('fetchUserPages returns empty array on exception', function () {
    Http::fake(function () {
        throw new Exception('Network error');
    });

    Log::shouldReceive('error')->once();

    $pages = $this->service->fetchUserPages('user_access_token');

    expect($pages)->toBe([]);
});

test('fetchUserPages returns empty array when no pages exist', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $pages = $this->service->fetchUserPages('user_access_token');

    expect($pages)->toBe([]);
});

test('fetchUserPages sends correct request parameters', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'data' => [],
        ], 200),
    ]);

    Log::shouldReceive('info')->once();

    $this->service->fetchUserPages('test_token');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/me/accounts')
            && $request->data()['access_token'] === 'test_token'
            && $request->data()['fields'] === 'id,name,access_token';
    });
});
