<?php

use App\Http\Middleware\ComingSoonMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Reset config for each test
    Config::set('app.coming_soon_mode', true);
    Config::set('app.coming_soon_bypass_token', 'secret-token');

    // Register a test route for testing purposes
    Route::get('/test-dashboard', fn () => 'Dashboard')->name('test.dashboard')->middleware(ComingSoonMode::class);
    Route::get('/coming-soon', fn () => 'Coming Soon')->name('coming-soon');
});

test('middleware allows request through in local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    $response = $this->get('/test-dashboard');

    $response->assertOk();
    $response->assertSee('Dashboard');
});

test('middleware allows request through in testing environment', function () {
    // Testing environment is the default in tests
    $response = $this->get('/test-dashboard');

    $response->assertOk();
    $response->assertSee('Dashboard');
});

test('middleware allows request when coming soon mode is disabled', function () {
    app()->detectEnvironment(fn () => 'production');
    Config::set('app.coming_soon_mode', false);

    $response = $this->get('/test-dashboard');

    $response->assertOk();
    $response->assertSee('Dashboard');
});

test('middleware redirects in production when mode is active', function () {
    app()->detectEnvironment(fn () => 'production');
    Config::set('app.coming_soon_mode', true);

    $response = $this->get('/test-dashboard');

    $response->assertRedirect(route('coming-soon'));
});

test('middleware allows coming soon route through in production', function () {
    app()->detectEnvironment(fn () => 'production');
    Config::set('app.coming_soon_mode', true);

    $response = $this->get('/coming-soon');

    $response->assertOk();
    $response->assertSee('Coming Soon');
});

test('middleware allows request with valid bypass token in query', function () {
    app()->detectEnvironment(fn () => 'production');
    Config::set('app.coming_soon_mode', true);
    Config::set('app.coming_soon_bypass_token', 'secret-token');

    $response = $this->get('/test-dashboard?preview=secret-token');

    $response->assertOk();
    $response->assertSee('Dashboard');
});

test('bypass token sets session for subsequent requests', function () {
    app()->detectEnvironment(fn () => 'production');
    Config::set('app.coming_soon_mode', true);
    Config::set('app.coming_soon_bypass_token', 'secret-token');

    // First request with token
    $response = $this->get('/test-dashboard?preview=secret-token');
    $response->assertOk();

    // Second request without token should still work due to session
    $response = $this->get('/test-dashboard');
    $response->assertOk();
});

test('middleware redirects when bypass token is not configured', function () {
    app()->detectEnvironment(fn () => 'production');
    Config::set('app.coming_soon_mode', true);
    Config::set('app.coming_soon_bypass_token', null);

    $response = $this->get('/test-dashboard?preview=any-token');

    $response->assertRedirect(route('coming-soon'));
});

test('middleware redirects when bypass token is invalid', function () {
    app()->detectEnvironment(fn () => 'production');
    Config::set('app.coming_soon_mode', true);
    Config::set('app.coming_soon_bypass_token', 'secret-token');

    $response = $this->get('/test-dashboard?preview=wrong-token');

    $response->assertRedirect(route('coming-soon'));
});
