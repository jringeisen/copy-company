<?php

use App\Enums\DedicatedIpStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Brand;
use App\Models\DedicatedIp;
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
        'dedicated_ip_address' => '54.240.1.1',
    ]);

    expect($brand->hasDedicatedIp())->toBeTrue();
});

test('brand has dedicated ip when status is warming', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Warming,
        'dedicated_ip_address' => '54.240.1.1',
        'warmup_day' => 5,
    ]);

    expect($brand->hasDedicatedIp())->toBeTrue();
    expect($brand->isInWarmupPeriod())->toBeTrue();
});

test('brand does not have dedicated ip when released', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Released,
    ]);

    expect($brand->hasDedicatedIp())->toBeFalse();
});

// Warmup Logic Tests

test('brand should always use dedicated ip when active', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Active,
        'dedicated_ip_address' => '54.240.1.1',
    ]);

    // Run 100 times to ensure it's always true for active status
    for ($i = 0; $i < 100; $i++) {
        expect($brand->shouldUseDedicatedIp())->toBeTrue();
    }
});

test('brand should never use dedicated ip when none', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::None,
    ]);

    for ($i = 0; $i < 100; $i++) {
        expect($brand->shouldUseDedicatedIp())->toBeFalse();
    }
});

test('brand uses percentage-based routing during warmup', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Warming,
        'dedicated_ip_address' => '54.240.1.1',
        'warmup_day' => 10, // Day 10 = 50% according to config
    ]);

    // Run many iterations and check that it's roughly 50%
    $usedDedicatedIp = 0;
    $iterations = 1000;

    for ($i = 0; $i < $iterations; $i++) {
        if ($brand->shouldUseDedicatedIp()) {
            $usedDedicatedIp++;
        }
    }

    // Should be roughly 50% with some variance
    $percentage = ($usedDedicatedIp / $iterations) * 100;
    expect($percentage)->toBeGreaterThan(40);
    expect($percentage)->toBeLessThan(60);
});

test('warmup progress is calculated correctly', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Warming,
        'warmup_day' => 10,
    ]);

    expect($brand->getWarmupProgress())->toBe(50);

    $brand->warmup_day = 20;
    expect($brand->getWarmupProgress())->toBe(100);

    $brand->warmup_day = 1;
    expect($brand->getWarmupProgress())->toBe(5);
});

test('warmup progress is 100 when active', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Active,
    ]);

    expect($brand->getWarmupProgress())->toBe(100);
});

test('warmup progress is 0 when not warming', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::None,
    ]);

    expect($brand->getWarmupProgress())->toBe(0);
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

// Dedicated IP Info Tests

test('brand returns correct dedicated ip info', function () {
    $brand = Brand::factory()->create([
        'dedicated_ip_status' => DedicatedIpStatus::Warming,
        'dedicated_ip_address' => '54.240.1.1',
        'warmup_day' => 10,
        'warmup_paused' => false,
    ]);

    $info = $brand->getDedicatedIpInfo();

    expect($info['status'])->toBe('warming');
    expect($info['status_label'])->toBe('Warming Up');
    expect($info['has_dedicated_ip'])->toBeTrue();
    expect($info['is_warming'])->toBeTrue();
    expect($info['warmup_day'])->toBe(10);
    expect($info['warmup_progress'])->toBe(50);
    expect($info['warmup_paused'])->toBeFalse();
    expect($info['ip_address'])->toBe('54.240.1.1');
});

// Dedicated IP Model Tests

test('dedicated ip can be created', function () {
    $ip = DedicatedIp::factory()->create([
        'ip_address' => '54.240.1.100',
        'status' => 'available',
    ]);

    expect($ip->ip_address)->toBe('54.240.1.100');
    expect($ip->isAvailable())->toBeTrue();
});

test('dedicated ip can be assigned to brand', function () {
    $brand = Brand::factory()->create();
    $ip = DedicatedIp::factory()->assigned()->create([
        'brand_id' => $brand->id,
    ]);

    expect($ip->isAssigned())->toBeTrue();
    expect($ip->brand->id)->toBe($brand->id);
});

test('available scope returns only available ips', function () {
    DedicatedIp::factory()->count(3)->create(['status' => 'available']);
    DedicatedIp::factory()->count(2)->create(['status' => 'assigned']);

    expect(DedicatedIp::available()->count())->toBe(3);
});

// Dedicated IP Log Tests

test('dedicated ip log can be created', function () {
    $brand = Brand::factory()->create();
    $user = User::factory()->create();

    $log = DedicatedIpLog::create([
        'brand_id' => $brand->id,
        'action' => 'ip_assigned',
        'ip_address' => '54.240.1.1',
        'metadata' => ['warmup_started' => true],
        'admin_user_id' => $user->id,
    ]);

    expect($log->action)->toBe('ip_assigned');
    expect($log->brand->id)->toBe($brand->id);
    expect($log->adminUser->id)->toBe($user->id);
    expect($log->metadata)->toBe(['warmup_started' => true]);
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
    expect(SubscriptionPlan::Pro->monthlyPriceCents())->toBe(5900);
    expect(SubscriptionPlan::Pro->annualPricePerMonthCents())->toBe(4900);
});

// DedicatedIpStatus Enum Tests

test('dedicated ip status has correct labels', function () {
    expect(DedicatedIpStatus::None->label())->toBe('No Dedicated IP');
    expect(DedicatedIpStatus::Provisioning->label())->toBe('Provisioning');
    expect(DedicatedIpStatus::Warming->label())->toBe('Warming Up');
    expect(DedicatedIpStatus::Active->label())->toBe('Active');
    expect(DedicatedIpStatus::Suspended->label())->toBe('Suspended');
    expect(DedicatedIpStatus::Released->label())->toBe('Released');
});

test('dedicated ip status knows if it can use dedicated ip', function () {
    expect(DedicatedIpStatus::None->canUseDedicatedIp())->toBeFalse();
    expect(DedicatedIpStatus::Provisioning->canUseDedicatedIp())->toBeFalse();
    expect(DedicatedIpStatus::Warming->canUseDedicatedIp())->toBeTrue();
    expect(DedicatedIpStatus::Active->canUseDedicatedIp())->toBeTrue();
    expect(DedicatedIpStatus::Suspended->canUseDedicatedIp())->toBeFalse();
    expect(DedicatedIpStatus::Released->canUseDedicatedIp())->toBeFalse();
});
