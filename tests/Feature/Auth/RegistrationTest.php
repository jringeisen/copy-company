<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'industry' => 'saas',
        'biggest_struggle' => 'growing_audience',
        'referral_source' => 'google',
    ]);

    $this->assertAuthenticated();
    // New users on trial are redirected to the subscribe page
    $response->assertRedirect('/billing/subscribe');

    // Verify onboarding fields were saved
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'industry' => 'saas',
        'biggest_struggle' => 'growing_audience',
        'referral_source' => 'google',
    ]);
});

test('registration requires onboarding fields', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['industry', 'biggest_struggle', 'referral_source']);
});
