<?php

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\EmailUsage;
use App\Models\Post;
use App\Models\User;
use App\Services\SubscriptionLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up fake Stripe price IDs for testing
    config([
        'services.stripe.prices.starter_monthly' => 'price_starter_monthly',
        'services.stripe.prices.starter_annual' => 'price_starter_annual',
        'services.stripe.prices.creator_monthly' => 'price_creator_monthly',
        'services.stripe.prices.creator_annual' => 'price_creator_annual',
        'services.stripe.prices.pro_monthly' => 'price_pro_monthly',
        'services.stripe.prices.pro_annual' => 'price_pro_annual',
    ]);
});

function createAccountWithSubscription(?SubscriptionPlan $plan = null, bool $onTrial = false): Account
{
    $account = Account::factory()->create([
        'trial_ends_at' => $onTrial ? now()->addDays(14) : null,
    ]);

    if ($plan) {
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->uuid(),
            'stripe_status' => 'active',
            'stripe_price' => $plan->monthlyPriceId(),
            'trial_ends_at' => $onTrial ? now()->addDays(14) : null,
        ]);
    }

    return $account;
}

// ============================================
// PLAN DETECTION TESTS
// ============================================

describe('Plan Detection', function () {
    test('returns null for account without subscription or trial', function () {
        $account = Account::factory()->create(['trial_ends_at' => null]);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPlan())->toBeNull();
    });

    test('returns Starter plan for account on generic trial', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPlan())->toBe(SubscriptionPlan::Starter);
    });

    test('returns correct plan for Starter subscription', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPlan())->toBe(SubscriptionPlan::Starter);
    });

    test('returns correct plan for Creator subscription', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPlan())->toBe(SubscriptionPlan::Creator);
    });

    test('returns correct plan for Pro subscription', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Pro);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPlan())->toBe(SubscriptionPlan::Pro);
    });
});

// ============================================
// POST LIMIT TESTS
// ============================================

describe('Post Limits', function () {
    test('Starter plan has 5 posts limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPostLimit())->toBe(5);
    });

    test('Creator plan has unlimited posts', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPostLimit())->toBeNull();
    });

    test('Pro plan has unlimited posts', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Pro);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPostLimit())->toBeNull();
    });

    test('canCreatePost returns true when under limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $brand = Brand::factory()->forAccount($account)->create();
        $user = User::factory()->create();

        // Create 4 posts (under limit of 5)
        Post::factory()->count(4)->forBrand($brand)->forUser($user)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->canCreatePost())->toBeTrue();
    });

    test('canCreatePost returns false when at limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $brand = Brand::factory()->forAccount($account)->create();
        $user = User::factory()->create();

        // Create 5 posts (at limit)
        Post::factory()->count(5)->forBrand($brand)->forUser($user)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->canCreatePost())->toBeFalse();
    });

    test('canCreatePost returns true for unlimited plans', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $brand = Brand::factory()->forAccount($account)->create();
        $user = User::factory()->create();

        // Create 100 posts
        Post::factory()->count(100)->forBrand($brand)->forUser($user)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->canCreatePost())->toBeTrue();
    });

    test('getRemainingPosts returns correct value', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $brand = Brand::factory()->forAccount($account)->create();
        $user = User::factory()->create();

        Post::factory()->count(3)->forBrand($brand)->forUser($user)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->getRemainingPosts())->toBe(2);
    });

    test('getRemainingPosts returns null for unlimited plans', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Pro);
        $service = new SubscriptionLimitsService($account);

        expect($service->getRemainingPosts())->toBeNull();
    });
});

// ============================================
// CONTENT SPRINT LIMIT TESTS
// ============================================

describe('Content Sprint Limits', function () {
    test('Starter plan has 1 sprint limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $service = new SubscriptionLimitsService($account);

        expect($service->getContentSprintLimit())->toBe(1);
    });

    test('Creator plan has 10 sprint limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $service = new SubscriptionLimitsService($account);

        expect($service->getContentSprintLimit())->toBe(10);
    });

    test('Pro plan has unlimited sprints', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Pro);
        $service = new SubscriptionLimitsService($account);

        expect($service->getContentSprintLimit())->toBeNull();
    });

    test('canCreateContentSprint returns true when under limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $brand = Brand::factory()->forAccount($account)->create();

        ContentSprint::factory()->count(5)->forBrand($brand)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->canCreateContentSprint())->toBeTrue();
    });

    test('canCreateContentSprint returns false when at limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $brand = Brand::factory()->forAccount($account)->create();

        ContentSprint::factory()->count(10)->forBrand($brand)->create();

        $service = new SubscriptionLimitsService($account);
        expect($service->canCreateContentSprint())->toBeFalse();
    });
});

// ============================================
// SOCIAL ACCOUNT LIMIT TESTS
// ============================================

describe('Social Account Limits', function () {
    test('Starter plan has 2 social accounts limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $service = new SubscriptionLimitsService($account);

        expect($service->getSocialAccountLimit())->toBe(2);
    });

    test('Creator plan has 5 social accounts limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $service = new SubscriptionLimitsService($account);

        expect($service->getSocialAccountLimit())->toBe(5);
    });

    test('Pro plan has 15 social accounts limit', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Pro);
        $service = new SubscriptionLimitsService($account);

        expect($service->getSocialAccountLimit())->toBe(15);
    });
});

// ============================================
// FEATURE ACCESS TESTS
// ============================================

describe('Feature Access', function () {
    test('Starter plan cannot use custom domain', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $service = new SubscriptionLimitsService($account);

        expect($service->canUseCustomDomain())->toBeFalse();
    });

    test('Creator plan can use custom domain', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $service = new SubscriptionLimitsService($account);

        expect($service->canUseCustomDomain())->toBeTrue();
    });

    test('Pro plan can use custom domain', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Pro);
        $service = new SubscriptionLimitsService($account);

        expect($service->canUseCustomDomain())->toBeTrue();
    });

    test('only Pro plan can use custom email domain', function () {
        $starter = createAccountWithSubscription(SubscriptionPlan::Starter);
        $creator = createAccountWithSubscription(SubscriptionPlan::Creator);
        $pro = createAccountWithSubscription(SubscriptionPlan::Pro);

        expect((new SubscriptionLimitsService($starter))->canUseCustomEmailDomain())->toBeFalse();
        expect((new SubscriptionLimitsService($creator))->canUseCustomEmailDomain())->toBeFalse();
        expect((new SubscriptionLimitsService($pro))->canUseCustomEmailDomain())->toBeTrue();
    });

    test('only Pro plan can remove branding', function () {
        $starter = createAccountWithSubscription(SubscriptionPlan::Starter);
        $creator = createAccountWithSubscription(SubscriptionPlan::Creator);
        $pro = createAccountWithSubscription(SubscriptionPlan::Pro);

        expect((new SubscriptionLimitsService($starter))->canRemoveBranding())->toBeFalse();
        expect((new SubscriptionLimitsService($creator))->canRemoveBranding())->toBeFalse();
        expect((new SubscriptionLimitsService($pro))->canRemoveBranding())->toBeTrue();
    });

    test('Creator and Pro plans have analytics', function () {
        $starter = createAccountWithSubscription(SubscriptionPlan::Starter);
        $creator = createAccountWithSubscription(SubscriptionPlan::Creator);
        $pro = createAccountWithSubscription(SubscriptionPlan::Pro);

        expect((new SubscriptionLimitsService($starter))->hasAnalytics())->toBeFalse();
        expect((new SubscriptionLimitsService($creator))->hasAnalytics())->toBeTrue();
        expect((new SubscriptionLimitsService($pro))->hasAnalytics())->toBeTrue();
    });
});

// ============================================
// USAGE SUMMARY TESTS
// ============================================

describe('Usage Summary', function () {
    test('getUsageSummary returns complete data', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $brand = Brand::factory()->forAccount($account)->create();
        $user = User::factory()->create();

        Post::factory()->count(3)->forBrand($brand)->forUser($user)->create();
        ContentSprint::factory()->count(2)->forBrand($brand)->create();

        $service = new SubscriptionLimitsService($account);
        $summary = $service->getUsageSummary();

        expect($summary)->toHaveKey('plan', 'creator')
            ->toHaveKey('plan_label', 'Creator')
            ->toHaveKey('on_trial')
            ->toHaveKey('posts')
            ->toHaveKey('content_sprints')
            ->toHaveKey('social_accounts')
            ->toHaveKey('features');

        expect($summary['posts']['used'])->toBe(3);
        expect($summary['content_sprints']['used'])->toBe(2);
        expect($summary['features']['custom_domain'])->toBeTrue();
        expect($summary['features']['analytics'])->toBeTrue();
    });
});

// ============================================
// TRIAL TESTS
// ============================================

describe('Trial Handling', function () {
    test('onTrial returns true for generic trial', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);
        $service = new SubscriptionLimitsService($account);

        expect($service->onTrial())->toBeTrue();
    });

    test('onTrial returns false for expired trial', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->subDay(),
        ]);
        $service = new SubscriptionLimitsService($account);

        expect($service->onTrial())->toBeFalse();
    });

    test('trialEndsAt returns correct date', function () {
        $trialEnd = now()->addDays(14);
        $account = Account::factory()->create([
            'trial_ends_at' => $trialEnd,
        ]);
        $service = new SubscriptionLimitsService($account);

        expect($service->trialEndsAt()->toDateString())->toBe($trialEnd->toDateString());
    });

    test('hasActiveSubscription returns true for trial', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);
        $service = new SubscriptionLimitsService($account);

        expect($service->hasActiveSubscription())->toBeTrue();
    });

    test('isOnFreeTrialOnly returns true for generic trial without subscription', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);
        $service = new SubscriptionLimitsService($account);

        expect($service->isOnFreeTrialOnly())->toBeTrue();
    });

    test('isOnFreeTrialOnly returns false for subscribed user', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Starter);
        $service = new SubscriptionLimitsService($account);

        expect($service->isOnFreeTrialOnly())->toBeFalse();
    });

    test('getSubscribedPlan returns null for free trial only', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);
        $service = new SubscriptionLimitsService($account);

        expect($service->getSubscribedPlan())->toBeNull();
    });

    test('getUsageSummary shows Free Trial label for trial users', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);
        $service = new SubscriptionLimitsService($account);
        $summary = $service->getUsageSummary();

        expect($summary['plan'])->toBeNull();
        expect($summary['plan_label'])->toBe('Free Trial');
        expect($summary['is_free_trial'])->toBeTrue();
    });

    test('free trial users have Starter limits', function () {
        $account = Account::factory()->create([
            'trial_ends_at' => now()->addDays(14),
        ]);
        $service = new SubscriptionLimitsService($account);

        expect($service->getPostLimit())->toBe(5);
        expect($service->getSocialAccountLimit())->toBe(2);
        expect($service->getContentSprintLimit())->toBe(1);
        expect($service->canUseCustomDomain())->toBeFalse();
    });
});

// ============================================
// PLAN TIER TESTS
// ============================================

describe('Plan Tiers', function () {
    test('isAtLeast correctly compares plan tiers', function () {
        $starterAccount = createAccountWithSubscription(SubscriptionPlan::Starter);
        $creatorAccount = createAccountWithSubscription(SubscriptionPlan::Creator);
        $proAccount = createAccountWithSubscription(SubscriptionPlan::Pro);

        $starterService = new SubscriptionLimitsService($starterAccount);
        $creatorService = new SubscriptionLimitsService($creatorAccount);
        $proService = new SubscriptionLimitsService($proAccount);

        expect($starterService->isAtLeast(SubscriptionPlan::Starter))->toBeTrue();
        expect($starterService->isAtLeast(SubscriptionPlan::Creator))->toBeFalse();
        expect($starterService->isAtLeast(SubscriptionPlan::Pro))->toBeFalse();

        expect($creatorService->isAtLeast(SubscriptionPlan::Starter))->toBeTrue();
        expect($creatorService->isAtLeast(SubscriptionPlan::Creator))->toBeTrue();
        expect($creatorService->isAtLeast(SubscriptionPlan::Pro))->toBeFalse();

        expect($proService->isAtLeast(SubscriptionPlan::Starter))->toBeTrue();
        expect($proService->isAtLeast(SubscriptionPlan::Creator))->toBeTrue();
        expect($proService->isAtLeast(SubscriptionPlan::Pro))->toBeTrue();
    });

    test('getRequiredPlanForFeature returns correct plans', function () {
        $account = Account::factory()->create();
        $service = new SubscriptionLimitsService($account);

        expect($service->getRequiredPlanForFeature('custom_domain'))->toBe(SubscriptionPlan::Creator);
        expect($service->getRequiredPlanForFeature('analytics'))->toBe(SubscriptionPlan::Creator);
        expect($service->getRequiredPlanForFeature('custom_email_domain'))->toBe(SubscriptionPlan::Pro);
        expect($service->getRequiredPlanForFeature('remove_branding'))->toBe(SubscriptionPlan::Pro);
    });
});

// ============================================
// EMAIL USAGE TESTS
// ============================================

describe('Email Usage', function () {
    test('getEmailsSentThisMonth returns correct count', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);

        // Create email usage records for this month
        EmailUsage::create([
            'account_id' => $account->id,
            'emails_sent' => 100,
            'period_date' => now()->startOfMonth(),
            'reported_to_stripe' => false,
        ]);

        EmailUsage::create([
            'account_id' => $account->id,
            'emails_sent' => 50,
            'period_date' => now()->startOfMonth()->addDays(5),
            'reported_to_stripe' => false,
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->getEmailsSentThisMonth())->toBe(150);
    });

    test('getEmailsSentThisMonth excludes previous months', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);

        // Create email usage record for last month (should not be counted)
        EmailUsage::create([
            'account_id' => $account->id,
            'emails_sent' => 500,
            'period_date' => now()->subMonth(),
            'reported_to_stripe' => true,
        ]);

        // Create email usage record for this month
        EmailUsage::create([
            'account_id' => $account->id,
            'emails_sent' => 100,
            'period_date' => now(),
            'reported_to_stripe' => false,
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->getEmailsSentThisMonth())->toBe(100);
    });

    test('getEmailsSentThisMonth returns zero when no emails sent', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $service = new SubscriptionLimitsService($account);

        expect($service->getEmailsSentThisMonth())->toBe(0);
    });

    test('getEstimatedEmailCost calculates correctly at $0.40 per 1000 emails', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);

        EmailUsage::create([
            'account_id' => $account->id,
            'emails_sent' => 1000,
            'period_date' => now(),
            'reported_to_stripe' => false,
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->getEstimatedEmailCost())->toBe(0.40);
    });

    test('getEstimatedEmailCost calculates correctly for 5000 emails', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);

        EmailUsage::create([
            'account_id' => $account->id,
            'emails_sent' => 5000,
            'period_date' => now(),
            'reported_to_stripe' => false,
        ]);

        $service = new SubscriptionLimitsService($account);

        expect($service->getEstimatedEmailCost())->toBe(2.00);
    });

    test('getEstimatedEmailCost returns zero when no emails sent', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);
        $service = new SubscriptionLimitsService($account);

        expect($service->getEstimatedEmailCost())->toBe(0.0);
    });

    test('getUsageSummary includes email usage data', function () {
        $account = createAccountWithSubscription(SubscriptionPlan::Creator);

        EmailUsage::create([
            'account_id' => $account->id,
            'emails_sent' => 2500,
            'period_date' => now(),
            'reported_to_stripe' => false,
        ]);

        $service = new SubscriptionLimitsService($account);
        $summary = $service->getUsageSummary();

        expect($summary)->toHaveKey('emails');
        expect($summary['emails']['sent'])->toBe(2500);
        expect($summary['emails']['estimated_cost'])->toBe(1.00);
        expect($summary['emails']['cost_per_thousand'])->toBe(0.40);
    });
});
