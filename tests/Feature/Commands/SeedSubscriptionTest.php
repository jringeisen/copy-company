<?php

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it seeds a subscription for an existing user', function () {
    $user = User::factory()->create(['email' => 'seed@example.com']);
    $account = Account::factory()->create();
    $user->accounts()->attach($account, ['role' => 'admin']);

    $this->artisan('subscription:seed', ['email' => 'seed@example.com', 'plan' => 'creator'])
        ->expectsOutputToContain('Seeded Creator subscription')
        ->assertExitCode(0);

    $account->refresh();

    expect($account->subscriptions)->toHaveCount(1);

    $subscription = $account->subscriptions->first();

    expect($subscription->stripe_status)->toBe('active');
    expect($subscription->stripe_price)->not->toBeEmpty();
    expect($subscription->items)->toHaveCount(1);
    expect(SubscriptionPlan::fromPriceId($subscription->stripe_price))->toBe(SubscriptionPlan::Creator);
});

test('it fails for nonexistent user', function () {
    $this->artisan('subscription:seed', ['email' => 'nobody@example.com', 'plan' => 'starter'])
        ->expectsOutputToContain('User not found')
        ->assertExitCode(1);
});

test('it fails for invalid plan', function () {
    $this->artisan('subscription:seed', ['email' => 'test@example.com', 'plan' => 'enterprise'])
        ->expectsOutputToContain('Invalid plan')
        ->assertExitCode(1);
});

test('it fails when user has no account', function () {
    User::factory()->create(['email' => 'noaccount@example.com']);

    $this->artisan('subscription:seed', ['email' => 'noaccount@example.com', 'plan' => 'starter'])
        ->expectsOutputToContain('no associated account')
        ->assertExitCode(1);
});

test('it replaces existing subscription when re-run', function () {
    $user = User::factory()->create(['email' => 'rerun@example.com']);
    $account = Account::factory()->create();
    $user->accounts()->attach($account, ['role' => 'admin']);

    $this->artisan('subscription:seed', ['email' => 'rerun@example.com', 'plan' => 'starter'])
        ->assertExitCode(0);

    $this->artisan('subscription:seed', ['email' => 'rerun@example.com', 'plan' => 'pro'])
        ->assertExitCode(0);

    $account->refresh();

    expect($account->subscriptions)->toHaveCount(1);
    expect(SubscriptionPlan::fromPriceId($account->subscriptions->first()->stripe_price))->toBe(SubscriptionPlan::Pro);
});

test('it works with all valid plans', function (string $plan, SubscriptionPlan $expectedPlan) {
    $user = User::factory()->create(['email' => "plan-{$plan}@example.com"]);
    $account = Account::factory()->create();
    $user->accounts()->attach($account, ['role' => 'admin']);

    $this->artisan('subscription:seed', ['email' => "plan-{$plan}@example.com", 'plan' => $plan])
        ->assertExitCode(0);

    $account->refresh();

    expect(SubscriptionPlan::fromPriceId($account->subscriptions->first()->stripe_price))->toBe($expectedPlan);
})->with([
    'starter' => ['starter', SubscriptionPlan::Starter],
    'creator' => ['creator', SubscriptionPlan::Creator],
    'pro' => ['pro', SubscriptionPlan::Pro],
]);
