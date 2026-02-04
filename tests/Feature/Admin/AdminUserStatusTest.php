<?php

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAdminUser(): array
{
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    return [$admin, $account];
}

function getUserStatus(object $testCase, User $admin, int $accountId, User $targetUser): string
{
    $response = $testCase->actingAs($admin)
        ->withSession(['current_account_id' => $accountId])
        ->get('/admin/users?search='.urlencode($targetUser->email));

    $response->assertSuccessful();

    $users = $response->original->getData()['page']['props']['users'];
    $match = collect($users)->firstWhere('email', $targetUser->email);

    return $match['status'];
}

test('user with no account shows No Account status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('No Account');
});

test('user on generic trial shows Trial status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create([
        'trial_ends_at' => now()->addDays(7),
    ]);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Trial');
});

test('user with active subscription shows Active status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_active_test',
        'stripe_status' => 'active',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
    ]);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Active');
});

test('user with subscription on trial shows Trial status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_trial_test',
        'stripe_status' => 'trialing',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        'trial_ends_at' => now()->addDays(14),
    ]);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Trial');
});

test('user with generic trial and active subscription shows Trial status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create([
        'trial_ends_at' => now()->addDays(7),
    ]);
    $account->users()->attach($user->id, ['role' => 'admin']);
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_with_trial_test',
        'stripe_status' => 'active',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
    ]);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Trial');
});

test('user with canceled subscription on grace period shows Canceled status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_grace_test',
        'stripe_status' => 'active',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        'ends_at' => now()->addDays(10),
    ]);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Canceled');
});

test('user with past due subscription shows Past Due status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_pastdue_test',
        'stripe_status' => 'past_due',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
    ]);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Past Due');
});

test('user with incomplete subscription shows Incomplete status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_incomplete_test',
        'stripe_status' => 'incomplete',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
    ]);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Incomplete');
});

test('user with fully canceled subscription shows Canceled status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_canceled_test',
        'stripe_status' => 'canceled',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        'ends_at' => now()->subDay(),
    ]);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Canceled');
});

test('user with paused subscription shows Paused status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_paused_test',
        'stripe_status' => 'paused',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
    ]);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Paused');
});

test('user with expired trial and no subscription shows Expired status', function () {
    [$admin, $adminAccount] = createAdminUser();

    $user = User::factory()->create();
    $account = Account::factory()->create([
        'trial_ends_at' => now()->subDay(),
    ]);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $status = getUserStatus($this, $admin, $adminAccount->id, $user);

    expect($status)->toBe('Expired');
});
