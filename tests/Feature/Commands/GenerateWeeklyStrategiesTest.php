<?php

use App\Models\Brand;
use App\Models\MarketingStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.stripe.prices.starter_monthly' => 'price_starter_monthly',
        'services.stripe.prices.starter_annual' => 'price_starter_annual',
        'services.stripe.prices.creator_monthly' => 'price_creator_monthly',
        'services.stripe.prices.creator_annual' => 'price_creator_annual',
        'services.stripe.prices.pro_monthly' => 'price_pro_monthly',
        'services.stripe.prices.pro_annual' => 'price_pro_annual',
    ]);
});

function setupBrandWithPlan(string $priceId): array
{
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $account = $user->accounts()->first();
    $account->createOrGetStripeCustomer();
    $subscription = $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);
    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    return [$user, $brand, $account];
}

test('command creates strategies for qualifying brands', function () {
    Queue::fake();

    [, $brand] = setupBrandWithPlan('price_creator_monthly');

    $this->artisan('strategies:generate-weekly')
        ->assertSuccessful();

    expect(MarketingStrategy::where('brand_id', $brand->id)->count())->toBe(1);
});

test('command skips brands that already have a strategy for the week', function () {
    Queue::fake();

    [, $brand] = setupBrandWithPlan('price_creator_monthly');

    // Pre-create a strategy for the upcoming week
    $weekStart = \Carbon\Carbon::now()->next(\Carbon\Carbon::MONDAY);
    MarketingStrategy::factory()->forBrand($brand)->create([
        'week_start' => $weekStart,
        'week_end' => $weekStart->copy()->endOfWeek(),
    ]);

    $this->artisan('strategies:generate-weekly')
        ->assertSuccessful();

    // Should still only have the one pre-existing strategy
    expect(MarketingStrategy::where('brand_id', $brand->id)->count())->toBe(1);
});

test('command skips brands on starter plan', function () {
    Queue::fake();

    [, $brand] = setupBrandWithPlan('price_starter_monthly');

    $this->artisan('strategies:generate-weekly')
        ->assertSuccessful();

    expect(MarketingStrategy::where('brand_id', $brand->id)->count())->toBe(0);
});

test('command creates strategies for pro plan brands', function () {
    Queue::fake();

    [, $brand] = setupBrandWithPlan('price_pro_monthly');

    $this->artisan('strategies:generate-weekly')
        ->assertSuccessful();

    expect(MarketingStrategy::where('brand_id', $brand->id)->count())->toBe(1);
});
