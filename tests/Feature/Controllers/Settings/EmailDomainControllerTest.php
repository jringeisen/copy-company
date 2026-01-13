<?php

use App\Models\Brand;
use App\Models\User;
use App\Services\SesDomainVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('email domain settings page loads for authenticated user', function () {
    $response = $this->actingAs($this->user)->get(route('settings.email-domain'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/EmailDomain')
        ->has('brand')
        ->has('defaultFromAddress')
    );
});

test('email domain settings page redirects to brand creation if no brand', function () {
    $userWithoutBrand = User::factory()->create();

    $response = $this->actingAs($userWithoutBrand)->get(route('settings.email-domain'));

    $response->assertRedirect(route('brands.create'));
});

test('can initiate domain verification', function () {
    $mockService = Mockery::mock(SesDomainVerificationService::class);
    $mockService->shouldReceive('initiateDomainVerification')
        ->once()
        ->with(Mockery::type(Brand::class), 'example.com')
        ->andReturn([
            'verification' => [
                'type' => 'TXT',
                'name' => '_amazonses.example.com',
                'value' => 'abc123',
            ],
            'dkim' => [],
            'spf' => [
                'type' => 'TXT',
                'name' => 'example.com',
                'value' => 'v=spf1 include:amazonses.com ~all',
            ],
        ]);

    $this->app->instance(SesDomainVerificationService::class, $mockService);

    $response = $this->actingAs($this->user)->post(route('settings.email-domain.initiate'), [
        'domain' => 'example.com',
        'from_address' => 'hello',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->brand->refresh();
    expect($this->brand->custom_email_from)->toBe('hello');
});

test('domain validation rejects invalid domains', function () {
    $response = $this->actingAs($this->user)->post(route('settings.email-domain.initiate'), [
        'domain' => 'not-a-valid-domain',
        'from_address' => 'hello',
    ]);

    $response->assertSessionHasErrors('domain');
});

test('domain validation rejects empty domain', function () {
    $response = $this->actingAs($this->user)->post(route('settings.email-domain.initiate'), [
        'domain' => '',
        'from_address' => 'hello',
    ]);

    $response->assertSessionHasErrors('domain');
});

test('can check verification status', function () {
    $this->brand->update([
        'custom_email_domain' => 'example.com',
        'email_domain_verification_status' => 'pending',
    ]);

    $mockService = Mockery::mock(SesDomainVerificationService::class);
    $mockService->shouldReceive('checkVerificationStatus')
        ->once()
        ->with(Mockery::type(Brand::class))
        ->andReturn('verified');

    $this->app->instance(SesDomainVerificationService::class, $mockService);

    $response = $this->actingAs($this->user)->post(route('settings.email-domain.check'));

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('check status fails when no domain configured', function () {
    $response = $this->actingAs($this->user)->post(route('settings.email-domain.check'));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'No domain configured.');
});

test('can update from address', function () {
    $this->brand->update([
        'custom_email_domain' => 'example.com',
        'custom_email_from' => 'hello',
        'email_domain_verification_status' => 'verified',
    ]);

    $response = $this->actingAs($this->user)->put(route('settings.email-domain.update-from'), [
        'from_address' => 'newsletter',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->brand->refresh();
    expect($this->brand->custom_email_from)->toBe('newsletter');
});

test('from address validation rejects invalid characters', function () {
    $this->brand->update([
        'custom_email_domain' => 'example.com',
    ]);

    $response = $this->actingAs($this->user)->put(route('settings.email-domain.update-from'), [
        'from_address' => 'invalid@address',
    ]);

    $response->assertSessionHasErrors('from_address');
});

test('can remove custom domain', function () {
    $this->brand->update([
        'custom_email_domain' => 'example.com',
        'custom_email_from' => 'hello',
        'email_domain_verification_status' => 'verified',
    ]);

    $mockService = Mockery::mock(SesDomainVerificationService::class);
    $mockService->shouldReceive('removeDomain')
        ->once()
        ->with(Mockery::type(Brand::class));

    $this->app->instance(SesDomainVerificationService::class, $mockService);

    $response = $this->actingAs($this->user)->delete(route('settings.email-domain.remove'));

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('remove domain fails when no domain configured', function () {
    $response = $this->actingAs($this->user)->delete(route('settings.email-domain.remove'));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'No domain configured.');
});

test('unauthenticated user cannot access email domain settings', function () {
    $response = $this->get(route('settings.email-domain'));

    $response->assertRedirect(route('login'));
});

test('handles service exception during initiation', function () {
    $mockService = Mockery::mock(SesDomainVerificationService::class);
    $mockService->shouldReceive('initiateDomainVerification')
        ->once()
        ->andThrow(new \Exception('AWS connection failed'));

    $this->app->instance(SesDomainVerificationService::class, $mockService);

    $response = $this->actingAs($this->user)->post(route('settings.email-domain.initiate'), [
        'domain' => 'example.com',
        'from_address' => 'hello',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('handles service exception during status check', function () {
    $this->brand->update([
        'custom_email_domain' => 'example.com',
    ]);

    $mockService = Mockery::mock(SesDomainVerificationService::class);
    $mockService->shouldReceive('checkVerificationStatus')
        ->once()
        ->andThrow(new \Exception('AWS connection failed'));

    $this->app->instance(SesDomainVerificationService::class, $mockService);

    $response = $this->actingAs($this->user)->post(route('settings.email-domain.check'));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('handles service exception during domain removal', function () {
    $this->brand->update([
        'custom_email_domain' => 'example.com',
        'custom_email_from' => 'hello',
        'email_domain_verification_status' => 'verified',
    ]);

    $mockService = Mockery::mock(SesDomainVerificationService::class);
    $mockService->shouldReceive('removeDomain')
        ->once()
        ->andThrow(new \Exception('AWS connection failed'));

    $this->app->instance(SesDomainVerificationService::class, $mockService);

    $response = $this->actingAs($this->user)->delete(route('settings.email-domain.remove'));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
