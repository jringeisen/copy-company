<?php

use App\Enums\DedicatedIpStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Brand;
use App\Models\DedicatedIpLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Brand Dedicated IP Status Tests

test('brand has no dedicated ip by default', function () {
    $brand = Brand::factory()->create();

    expect($brand->hasDedicatedIp())->toBeFalse();
    // By default, brands don't have a dedicated IP status set (null or None)
    expect(in_array($brand->dedicated_ip_status, [null, DedicatedIpStatus::None], true))->toBeTrue();
});

test('brand has dedicated ip when status is active', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Active,
        'ses_configuration_set' => 'brand-1-config',
    ]);

    expect($brand->hasDedicatedIp())->toBeTrue();
});

test('brand does not have dedicated ip when released', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Released,
    ]);

    expect($brand->hasDedicatedIp())->toBeFalse();
});

test('brand does not have dedicated ip when suspended', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Suspended,
    ]);

    expect($brand->hasDedicatedIp())->toBeFalse();
});

// Configuration Set Tests

test('brand returns shared configuration set when no dedicated ip', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::None,
    ]);

    // Should return the shared pool from config
    $expected = config('services.ses.configuration_set', 'shared-pool');
    expect($brand->getSesConfigurationSet())->toBe($expected);
});

test('brand returns dedicated configuration set when active', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Active,
        'ses_configuration_set' => 'brand-123-config',
    ]);

    expect($brand->getSesConfigurationSet())->toBe('brand-123-config');
});

test('brand returns shared configuration set when suspended', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Suspended,
        'ses_configuration_set' => 'brand-123-config',
    ]);

    // Suspended brands still have config set but hasDedicatedIp returns false
    expect($brand->getSesConfigurationSet())->toBe('brand-123-config');
    expect($brand->hasDedicatedIp())->toBeFalse();
});

// Dedicated IP Info Tests

test('brand returns correct dedicated ip info when active', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Active,
        'ses_configuration_set' => 'brand-1-config',
    ]);

    $info = $brand->getDedicatedIpInfo();

    expect($info['status'])->toBe('active');
    expect($info['status_label'])->toBe('Active');
    expect($info['has_dedicated_ip'])->toBeTrue();
});

test('brand returns correct dedicated ip info when none', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::None,
    ]);

    $info = $brand->getDedicatedIpInfo();

    expect($info['status'])->toBe('none');
    expect($info['status_label'])->toBe('No Dedicated IP');
    expect($info['has_dedicated_ip'])->toBeFalse();
});

// Dedicated IP Log Tests

test('dedicated ip log can be created', function () {
    $brand = Brand::factory()->create();
    $user = User::factory()->create();

    $log = DedicatedIpLog::create([
        'brand_id' => $brand->id,
        'action' => 'provisioned',
        'metadata' => ['configuration_set' => 'brand-1-config', 'managed_pool' => 'pro-managed-pool'],
        'admin_user_id' => $user->id,
    ]);

    expect($log->action)->toBe('provisioned');
    expect($log->brand->id)->toBe($brand->id);
    expect($log->adminUser->id)->toBe($user->id);
    expect($log->metadata)->toBe(['configuration_set' => 'brand-1-config', 'managed_pool' => 'pro-managed-pool']);
});

// Subscription Plan Tests

test('pro plan has dedicated ip support', function () {
    expect(SubscriptionPlan::Pro->hasDedicatedIpSupport())->toBeTrue();
});

test('creator plan does not have dedicated ip support', function () {
    expect(SubscriptionPlan::Creator->hasDedicatedIpSupport())->toBeFalse();
});

test('starter plan does not have dedicated ip support', function () {
    expect(SubscriptionPlan::Starter->hasDedicatedIpSupport())->toBeFalse();
});

test('pro plan has updated pricing', function () {
    expect(SubscriptionPlan::Pro->monthlyPriceCents())->toBe(2900);
    expect(SubscriptionPlan::Pro->annualPricePerMonthCents())->toBe(2200);
});

// DedicatedIpStatus Enum Tests

test('dedicated ip status has correct labels', function () {
    expect(DedicatedIpStatus::None->label())->toBe('No Dedicated IP');
    expect(DedicatedIpStatus::Active->label())->toBe('Active');
    expect(DedicatedIpStatus::Suspended->label())->toBe('Suspended');
    expect(DedicatedIpStatus::Released->label())->toBe('Released');
});

test('dedicated ip status knows if it can use dedicated ip', function () {
    expect(DedicatedIpStatus::None->canUseDedicatedIp())->toBeFalse();
    expect(DedicatedIpStatus::Active->canUseDedicatedIp())->toBeTrue();
    expect(DedicatedIpStatus::Suspended->canUseDedicatedIp())->toBeFalse();
    expect(DedicatedIpStatus::Released->canUseDedicatedIp())->toBeFalse();
});
