<?php

use App\Enums\SubscriptionPlan;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

// ==========================================
// INDEX TESTS
// ==========================================

test('guests cannot access billing page', function () {
    $response = $this->get(route('settings.billing'));

    $response->assertRedirect('/login');
});

test('users without account are redirected to dashboard with error', function () {
    $user = User::factory()->create();

    // Ensure user has no accounts
    $user->accounts()->detach();

    $response = $this->actingAs($user)->get(route('settings.billing'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'No account found.');
});

test('users can view billing page', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('settings.billing'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/Billing')
        ->has('usage')
        ->has('subscription')
        ->has('invoices')
        ->has('has_payment_method')
    );
});

test('billing page shows null subscription for non-subscribed users', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('settings.billing'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('subscription', null)
    );
});

test('billing page shows subscription details for subscribed users', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();
    $account = $user->currentAccount();

    // Create subscription
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_123',
        'stripe_status' => 'active',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
    ]);

    $response = $this->actingAs($user)->get(route('settings.billing'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('subscription')
        ->where('subscription.stripe_status', 'active')
        ->where('subscription.canceled', false)
    );
});

test('billing page shows empty invoices for users without stripe id', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('settings.billing'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('invoices', [])
    );
});

// ==========================================
// SUBSCRIBE PAGE TESTS
// ==========================================

test('guests cannot access subscribe page', function () {
    $response = $this->get(route('billing.subscribe'));

    $response->assertRedirect('/login');
});

test('users can view subscribe page', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('billing.subscribe'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Billing/Subscribe')
        ->has('plans')
        ->has('current_plan')
        ->has('on_trial')
        ->has('trial_ends_at')
    );
});

test('subscribe page shows all available plans', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('billing.subscribe'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('plans', 3) // starter, creator, pro
        ->where('plans.0.value', 'starter')
        ->where('plans.1.value', 'creator')
        ->where('plans.2.value', 'pro')
    );
});

test('subscribe page shows trial info for users on trial', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();
    $account = $user->currentAccount();

    $trialEnds = now()->addDays(14);
    $account->update(['trial_ends_at' => $trialEnds]);

    $response = $this->actingAs($user)->get(route('billing.subscribe'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('on_trial', true)
        ->where('trial_ends_at', $trialEnds->format('M j, Y'))
    );
});

test('subscribe page marks current plan for subscribed users', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();
    $account = $user->currentAccount();

    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_123',
        'stripe_status' => 'active',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
    ]);

    $response = $this->actingAs($user)->get(route('billing.subscribe'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('current_plan', 'creator')
    );
});

// ==========================================
// CHECKOUT TESTS
// ==========================================

test('guests cannot access checkout', function () {
    $response = $this->post(route('billing.checkout'), [
        'plan' => 'creator',
        'interval' => 'monthly',
    ]);

    $response->assertRedirect('/login');
});

test('checkout requires plan parameter', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('billing.checkout'), [
        'interval' => 'monthly',
    ]);

    $response->assertSessionHasErrors('plan');
});

test('checkout requires interval parameter', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'creator',
    ]);

    $response->assertSessionHasErrors('interval');
});

test('checkout validates plan is one of starter creator pro', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'invalid',
        'interval' => 'monthly',
    ]);

    $response->assertSessionHasErrors('plan');
});

test('checkout validates interval is monthly or annual', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'creator',
        'interval' => 'weekly',
    ]);

    $response->assertSessionHasErrors('interval');
});

test('checkout returns error for users without account', function () {
    $user = User::factory()->create();
    $user->accounts()->detach();

    $response = $this->actingAs($user)->post(route('billing.checkout'), [
        'plan' => 'creator',
        'interval' => 'monthly',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'No account found.');
});

// ==========================================
// PORTAL TESTS
// ==========================================

test('guests cannot access portal', function () {
    $response = $this->get(route('billing.portal'));

    $response->assertRedirect('/login');
});

test('portal returns error for users without account', function () {
    $user = User::factory()->create();
    $user->accounts()->detach();

    $response = $this->actingAs($user)->get(route('billing.portal'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'No account found.');
});

// ==========================================
// RESUME TESTS
// ==========================================

test('guests cannot resume subscription', function () {
    $response = $this->post(route('billing.resume'));

    $response->assertRedirect('/login');
});

test('resume returns error for users without account', function () {
    $user = User::factory()->create();
    $user->accounts()->detach();

    $response = $this->actingAs($user)->post(route('billing.resume'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'No account found.');
});

test('resume returns error when no subscription on grace period', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('billing.resume'));

    $response->assertRedirect(route('settings.billing'));
    $response->assertSessionHas('error', 'Unable to resume subscription.');
});

test('resume returns error for active subscription not on grace period', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();
    $account = $user->currentAccount();

    // Create active subscription without ends_at
    $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_123',
        'stripe_status' => 'active',
        'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
    ]);

    $response = $this->actingAs($user)->post(route('billing.resume'));

    $response->assertRedirect(route('settings.billing'));
    $response->assertSessionHas('error', 'Unable to resume subscription.');
});

// ==========================================
// CANCEL TESTS
// ==========================================

test('guests cannot cancel subscription', function () {
    $response = $this->post(route('billing.cancel'));

    $response->assertRedirect('/login');
});

test('cancel returns error for users without account', function () {
    $user = User::factory()->create();
    $user->accounts()->detach();

    $response = $this->actingAs($user)->post(route('billing.cancel'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'No account found.');
});

test('cancel returns error when no active subscription', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('billing.cancel'));

    $response->assertRedirect(route('settings.billing'));
    $response->assertSessionHas('error', 'No active subscription to cancel.');
});

// ==========================================
// DOWNLOAD INVOICE TESTS
// ==========================================

test('guests cannot download invoices', function () {
    $response = $this->get(route('billing.invoice.download', 'inv_123'));

    $response->assertRedirect('/login');
});

test('download invoice returns 404 for users without account', function () {
    $user = User::factory()->create();
    $user->accounts()->detach();

    $response = $this->actingAs($user)->get(route('billing.invoice.download', 'inv_123'));

    $response->assertNotFound();
});
