<?php

test('privacy policy page is accessible', function () {
    $response = $this->get('/privacy-policy');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('PrivacyPolicy'));
});

test('terms of service page is accessible', function () {
    $response = $this->get('/terms-of-service');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('TermsOfService'));
});

test('privacy policy route is named correctly', function () {
    expect(route('privacy-policy'))->toEndWith('/privacy-policy');
});

test('terms of service route is named correctly', function () {
    expect(route('terms-of-service'))->toEndWith('/terms-of-service');
});
