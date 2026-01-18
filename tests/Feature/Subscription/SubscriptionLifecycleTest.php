<?php

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\Post;
use App\Models\User;
use App\Services\SubscriptionLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up fake Stripe price IDs
    config([
        'services.stripe.prices.starter_monthly' => 'price_starter_monthly',
        'services.stripe.prices.starter_annual' => 'price_starter_annual',
        'services.stripe.prices.creator_monthly' => 'price_creator_monthly',
        'services.stripe.prices.creator_annual' => 'price_creator_annual',
        'services.stripe.prices.pro_monthly' => 'price_pro_monthly',
        'services.stripe.prices.pro_annual' => 'price_pro_annual',
    ]);

    // Create permissions
    Permission::findOrCreate('posts.create', 'web');
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo('posts.create');
});

// ============================================
// TRIAL TO SUBSCRIPTION TRANSITION
// ============================================

describe('Trial to Subscription Transition', function () {
    test('trial user gains full access after subscribing', function () {
        // Start with a trial user
        $account = Account::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);

        $service = new SubscriptionLimitsService($account);

        // Verify trial state
        expect($service->onTrial())->toBeTrue();
        expect($service->isOnFreeTrialOnly())->toBeTrue();
        expect($service->getSubscribedPlan())->toBeNull();

        // User subscribes to Creator plan
        $account->update(['trial_ends_at' => null]); // Clear generic trial
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_new',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        ]);

        // Refresh service with updated account
        $service = new SubscriptionLimitsService($account->fresh());

        // Verify subscribed state
        expect($service->isOnFreeTrialOnly())->toBeFalse();
        expect($service->getSubscribedPlan())->toBe(SubscriptionPlan::Creator);
        expect($service->canUseCustomDomain())->toBeTrue();
        expect($service->hasAnalytics())->toBeTrue();
    });

    test('expired trial user loses access until subscribing', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->subDay(), // Expired
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->onTrial())->toBeFalse();
        expect($service->getPlan())->toBeNull();
        expect($service->hasActiveSubscription())->toBeFalse();

        // After subscribing
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_after_trial',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Starter->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account->fresh());

        expect($service->hasActiveSubscription())->toBeTrue();
        expect($service->getPlan())->toBe(SubscriptionPlan::Starter);
    });

    test('trial user maintains access during subscription trial period', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => null,
        ]);

        // Create subscription with trial
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_with_trial',
            'stripe_status' => 'trialing',
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->onTrial())->toBeTrue();
        expect($service->getSubscribedPlan())->toBe(SubscriptionPlan::Creator);
        expect($service->isOnFreeTrialOnly())->toBeFalse();
        // Has Creator features during trial
        expect($service->canUseCustomDomain())->toBeTrue();
    });
});

// ============================================
// PLAN UPGRADES
// ============================================

describe('Plan Upgrades', function () {
    test('upgrading from Starter to Creator unlocks features', function () {
        $account = Account::factory()->create();
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_starter',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Starter->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account);

        // Starter limits
        expect($service->getPlan())->toBe(SubscriptionPlan::Starter);
        expect($service->getPostLimit())->toBe(5);
        expect($service->canUseCustomDomain())->toBeFalse();
        expect($service->hasAnalytics())->toBeFalse();

        // Simulate upgrade to Creator
        $subscription->update([
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account->fresh());

        // Creator features
        expect($service->getPlan())->toBe(SubscriptionPlan::Creator);
        expect($service->getPostLimit())->toBeNull(); // Unlimited
        expect($service->canUseCustomDomain())->toBeTrue();
        expect($service->hasAnalytics())->toBeTrue();
    });

    test('upgrading from Creator to Pro unlocks all features', function () {
        $account = Account::factory()->create();
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_creator',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account);

        // Creator limits
        expect($service->canUseCustomEmailDomain())->toBeFalse();
        expect($service->canRemoveBranding())->toBeFalse();
        expect($service->getSocialAccountLimit())->toBe(5);

        // Simulate upgrade to Pro
        $subscription->update([
            'stripe_price' => SubscriptionPlan::Pro->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account->fresh());

        // Pro features
        expect($service->getPlan())->toBe(SubscriptionPlan::Pro);
        expect($service->canUseCustomEmailDomain())->toBeTrue();
        expect($service->canRemoveBranding())->toBeTrue();
        expect($service->getSocialAccountLimit())->toBe(15);
    });

    test('upgrade immediately increases limits', function () {
        $account = Account::factory()->create();
        $brand = Brand::factory()->forAccount($account)->create();
        $user = User::factory()->create();

        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_limit_test',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Starter->monthlyPriceId(),
        ]);

        // Create 5 posts (at Starter limit)
        Post::factory()->count(5)->forBrand($brand)->forUser($user)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->canCreatePost())->toBeFalse();

        // Upgrade to Creator
        $subscription->update([
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account->fresh());
        expect($service->canCreatePost())->toBeTrue(); // Now unlimited
    });
});

// ============================================
// PLAN DOWNGRADES
// ============================================

describe('Plan Downgrades', function () {
    test('downgrading from Pro to Creator removes Pro features', function () {
        $account = Account::factory()->create();
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_pro',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Pro->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account);
        expect($service->canUseCustomEmailDomain())->toBeTrue();
        expect($service->canRemoveBranding())->toBeTrue();

        // Downgrade to Creator
        $subscription->update([
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account->fresh());

        expect($service->getPlan())->toBe(SubscriptionPlan::Creator);
        expect($service->canUseCustomEmailDomain())->toBeFalse();
        expect($service->canRemoveBranding())->toBeFalse();
        expect($service->canUseCustomDomain())->toBeTrue(); // Still has Creator feature
    });

    test('downgrading from Creator to Starter enforces post limits', function () {
        $account = Account::factory()->create();
        $brand = Brand::factory()->forAccount($account)->create();
        $user = User::factory()->create();

        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_downgrade',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        ]);

        // Create 10 posts (within Creator unlimited)
        Post::factory()->count(10)->forBrand($brand)->forUser($user)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->canCreatePost())->toBeTrue();

        // Downgrade to Starter
        $subscription->update([
            'stripe_price' => SubscriptionPlan::Starter->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account->fresh());

        // Now over the 5 post limit
        expect($service->getPostLimit())->toBe(5);
        expect($service->getPostsThisMonth())->toBe(10);
        expect($service->canCreatePost())->toBeFalse();
        expect($service->getRemainingPosts())->toBe(0);
    });

    test('downgrade reduces content sprint limit', function () {
        $account = Account::factory()->create();
        $brand = Brand::factory()->forAccount($account)->create();

        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_sprint_downgrade',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        ]);

        // Create 8 content sprints (within Creator's 10 limit)
        ContentSprint::factory()->count(8)->forBrand($brand)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->canCreateContentSprint())->toBeTrue();

        // Downgrade to Starter (1 sprint limit)
        $subscription->update([
            'stripe_price' => SubscriptionPlan::Starter->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account->fresh());

        expect($service->getContentSprintLimit())->toBe(1);
        expect($service->canCreateContentSprint())->toBeFalse();
    });
});

// ============================================
// SUBSCRIPTION CANCELLATION
// ============================================

describe('Subscription Cancellation', function () {
    test('cancelled subscription with grace period maintains access', function () {
        $account = Account::factory()->create();
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_cancel_grace',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
            'ends_at' => now()->addDays(14), // Grace period
        ]);

        expect($subscription->canceled())->toBeTrue();
        expect($subscription->onGracePeriod())->toBeTrue();
        expect($subscription->valid())->toBeTrue();

        $service = new SubscriptionLimitsService($account);
        expect($service->hasActiveSubscription())->toBeTrue();
        expect($service->getPlan())->toBe(SubscriptionPlan::Creator);
    });

    test('cancelled subscription after grace period loses access', function () {
        $account = Account::factory()->create();
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_cancel_expired',
            'stripe_status' => 'canceled',
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
            'ends_at' => now()->subDay(), // Grace period ended
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->hasActiveSubscription())->toBeFalse();
        expect($service->getPlan())->toBeNull();
    });

    test('resumed subscription restores access', function () {
        $account = Account::factory()->create();
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_resume',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Pro->monthlyPriceId(),
            'ends_at' => now()->addDays(7), // Was cancelled, on grace period
        ]);

        // Resume subscription
        $subscription->update(['ends_at' => null]);

        $service = new SubscriptionLimitsService($account->fresh());

        expect($service->hasActiveSubscription())->toBeTrue();
        expect($service->getPlan())->toBe(SubscriptionPlan::Pro);
        expect($subscription->fresh()->canceled())->toBeFalse();
    });
});

// ============================================
// MULTI-BRAND LIMIT SCENARIOS
// ============================================

describe('Multi-Brand Limit Scenarios', function () {
    test('limits are shared across all brands in account', function () {
        $account = Account::factory()->create();
        $user = User::factory()->create();

        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_multi_brand',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Starter->monthlyPriceId(),
        ]);

        // Create two brands
        $brand1 = Brand::factory()->forAccount($account)->create();
        $brand2 = Brand::factory()->forAccount($account)->create();

        // Create 3 posts on brand 1 and 2 posts on brand 2 (total 5, at limit)
        Post::factory()->count(3)->forBrand($brand1)->forUser($user)->create();
        Post::factory()->count(2)->forBrand($brand2)->forUser($user)->create();

        $service = new SubscriptionLimitsService($account);

        expect($service->getPostsThisMonth())->toBe(5);
        expect($service->canCreatePost())->toBeFalse();
    });

    test('social accounts are counted across all brands', function () {
        $account = Account::factory()->create();

        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_social_count',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Starter->monthlyPriceId(),
        ]);

        // Create brands with social connections
        Brand::factory()->forAccount($account)->create([
            'social_connections' => ['twitter' => ['access_token' => 'xxx']],
        ]);
        Brand::factory()->forAccount($account)->create([
            'social_connections' => ['facebook' => ['access_token' => 'yyy']],
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->getSocialAccountCount())->toBe(2);
        expect($service->getSocialAccountLimit())->toBe(2); // Starter limit
        expect($service->canAddSocialAccount())->toBeFalse();
    });
});

// ============================================
// BILLING INTERVAL CHANGES
// ============================================

describe('Billing Interval Changes', function () {
    test('switching from monthly to annual maintains same plan', function () {
        $account = Account::factory()->create();
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_interval_change',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Creator->monthlyPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account);
        expect($service->getPlan())->toBe(SubscriptionPlan::Creator);

        // Switch to annual
        $subscription->update([
            'stripe_price' => SubscriptionPlan::Creator->annualPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account->fresh());
        expect($service->getPlan())->toBe(SubscriptionPlan::Creator);
    });

    test('annual pricing is recognized correctly', function () {
        $account = Account::factory()->create();
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_annual',
            'stripe_status' => 'active',
            'stripe_price' => SubscriptionPlan::Pro->annualPriceId(),
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->getPlan())->toBe(SubscriptionPlan::Pro);
        expect($service->canUseCustomEmailDomain())->toBeTrue();
        expect($service->canRemoveBranding())->toBeTrue();
    });
});
