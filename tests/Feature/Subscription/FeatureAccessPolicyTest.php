<?php

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\Post;
use App\Models\User;
use App\Policies\ContentSprintPolicy;
use App\Policies\PostPolicy;
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
    Permission::findOrCreate('posts.update', 'web');
    Permission::findOrCreate('posts.delete', 'web');
    Permission::findOrCreate('posts.publish', 'web');
    Permission::findOrCreate('sprints.create', 'web');
    Permission::findOrCreate('sprints.manage', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'posts.create',
        'posts.update',
        'posts.delete',
        'posts.publish',
        'sprints.create',
        'sprints.manage',
    ]);
});

function createUserWithPlan(?SubscriptionPlan $plan = null, bool $onTrial = false): User
{
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'trial_ends_at' => $onTrial && ! $plan ? now()->addDays(14) : null,
    ]);
    $account->users()->attach($user->id, ['role' => 'admin']);

    if ($plan) {
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->uuid(),
            'stripe_status' => 'active',
            'stripe_price' => $plan->monthlyPriceId(),
        ]);
    }

    $brand = Brand::factory()->forAccount($account)->create();
    $user->update(['current_brand_id' => $brand->id]);

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    return $user;
}

// ============================================
// POST POLICY SUBSCRIPTION TESTS
// ============================================

describe('PostPolicy Subscription Enforcement', function () {
    test('policy allows post creation when under Starter limit', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);
        $brand = $user->currentBrand();

        // Create 4 posts (under limit of 5)
        Post::factory()->count(4)->forBrand($brand)->forUser($user)->create();

        $policy = new PostPolicy;

        expect($policy->create($user))->toBeTrue();
    });

    test('policy denies post creation when at Starter limit', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);
        $brand = $user->currentBrand();

        // Create 5 posts (at limit)
        Post::factory()->count(5)->forBrand($brand)->forUser($user)->create();

        $policy = new PostPolicy;

        expect($policy->create($user))->toBeFalse();
    });

    test('policy allows unlimited posts for Creator plan', function () {
        $user = createUserWithPlan(SubscriptionPlan::Creator);
        $brand = $user->currentBrand();

        // Create many posts
        Post::factory()->count(50)->forBrand($brand)->forUser($user)->create();

        $policy = new PostPolicy;

        expect($policy->create($user))->toBeTrue();
    });

    test('policy allows unlimited posts for Pro plan', function () {
        $user = createUserWithPlan(SubscriptionPlan::Pro);
        $brand = $user->currentBrand();

        // Create many posts
        Post::factory()->count(100)->forBrand($brand)->forUser($user)->create();

        $policy = new PostPolicy;

        expect($policy->create($user))->toBeTrue();
    });

    test('policy allows post creation for trial users under limit', function () {
        $user = createUserWithPlan(null, true);
        $brand = $user->currentBrand();

        // Create 3 posts (under Starter limit of 5)
        Post::factory()->count(3)->forBrand($brand)->forUser($user)->create();

        $policy = new PostPolicy;

        expect($policy->create($user))->toBeTrue();
    });

    test('policy denies post creation for trial users at limit', function () {
        $user = createUserWithPlan(null, true);
        $brand = $user->currentBrand();

        // Create 5 posts (at Starter limit)
        Post::factory()->count(5)->forBrand($brand)->forUser($user)->create();

        $policy = new PostPolicy;

        expect($policy->create($user))->toBeFalse();
    });

    test('policy behavior for users without subscription or trial', function () {
        $user = User::factory()->create();
        $account = Account::factory()->create(['trial_ends_at' => null]);
        $account->users()->attach($user->id, ['role' => 'admin']);
        $brand = Brand::factory()->forAccount($account)->create();
        $user->update(['current_brand_id' => $brand->id]);

        setPermissionsTeamId($account->id);
        $user->assignRole('admin');

        // Verify user has no subscription/trial
        $limits = $account->subscriptionLimits();
        expect($limits->getPlan())->toBeNull();
        expect($limits->hasActiveSubscription())->toBeFalse();

        // canCreatePost returns true when plan is null (no limit to check)
        // because the method returns true if limit is null
        expect($limits->canCreatePost())->toBeTrue();
    });
});

// ============================================
// CONTENT SPRINT POLICY TESTS
// ============================================

describe('ContentSprintPolicy Subscription Enforcement', function () {
    test('policy allows sprint creation when under Starter limit', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);

        // Starter has 1 sprint limit, no sprints yet
        $policy = new ContentSprintPolicy;

        expect($policy->create($user))->toBeTrue();
    });

    test('policy denies sprint creation when at Starter limit', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);
        $brand = $user->currentBrand();

        // Create 1 sprint (at Starter limit)
        ContentSprint::factory()->forBrand($brand)->create();

        $policy = new ContentSprintPolicy;

        expect($policy->create($user))->toBeFalse();
    });

    test('policy allows sprint creation when under Creator limit', function () {
        $user = createUserWithPlan(SubscriptionPlan::Creator);
        $brand = $user->currentBrand();

        // Create 8 sprints (under Creator limit of 10)
        ContentSprint::factory()->count(8)->forBrand($brand)->create();

        $policy = new ContentSprintPolicy;

        expect($policy->create($user))->toBeTrue();
    });

    test('policy denies sprint creation when at Creator limit', function () {
        $user = createUserWithPlan(SubscriptionPlan::Creator);
        $brand = $user->currentBrand();

        // Create 10 sprints (at Creator limit)
        ContentSprint::factory()->count(10)->forBrand($brand)->create();

        $policy = new ContentSprintPolicy;

        expect($policy->create($user))->toBeFalse();
    });

    test('policy allows unlimited sprints for Pro plan', function () {
        $user = createUserWithPlan(SubscriptionPlan::Pro);
        $brand = $user->currentBrand();

        // Create many sprints
        ContentSprint::factory()->count(50)->forBrand($brand)->create();

        $policy = new ContentSprintPolicy;

        expect($policy->create($user))->toBeTrue();
    });
});

// ============================================
// CONTROLLER-LEVEL POLICY ENFORCEMENT
// ============================================

describe('Controller Post Limit Enforcement', function () {
    test('controller rejects post creation when at limit', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);
        $brand = $user->currentBrand();

        // Create 5 posts (at limit)
        Post::factory()->count(5)->forBrand($brand)->forUser($user)->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'New Post',
            'content' => [['type' => 'paragraph', 'content' => 'Content']],
            'content_html' => '<p>Content</p>',
        ]);

        $response->assertForbidden();
    });

    test('controller allows post creation when under limit', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);
        $brand = $user->currentBrand();

        // Create 3 posts (under limit)
        Post::factory()->count(3)->forBrand($brand)->forUser($user)->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'New Post',
            'content' => [['type' => 'paragraph', 'content' => 'Content']],
            'content_html' => '<p>Content</p>',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'brand_id' => $brand->id,
            'title' => 'New Post',
        ]);
    });

    test('controller allows post creation for unlimited plan', function () {
        $user = createUserWithPlan(SubscriptionPlan::Creator);
        $brand = $user->currentBrand();

        // Create many posts
        Post::factory()->count(20)->forBrand($brand)->forUser($user)->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Another Post',
            'content' => [['type' => 'paragraph', 'content' => 'Content']],
            'content_html' => '<p>Content</p>',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'brand_id' => $brand->id,
            'title' => 'Another Post',
        ]);
    });
});

// ============================================
// FEATURE-SPECIFIC ACCESS TESTS
// ============================================

describe('Feature-Specific Access Control', function () {
    test('Starter users cannot access custom domain feature', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);
        $account = $user->currentAccount();
        $limits = $account->subscriptionLimits();

        expect($limits->canUseCustomDomain())->toBeFalse();
        expect($limits->getRequiredPlanForFeature('custom_domain'))->toBe(SubscriptionPlan::Creator);
    });

    test('Creator users can access custom domain feature', function () {
        $user = createUserWithPlan(SubscriptionPlan::Creator);
        $account = $user->currentAccount();
        $limits = $account->subscriptionLimits();

        expect($limits->canUseCustomDomain())->toBeTrue();
    });

    test('only Pro users can access custom email domain', function () {
        $starter = createUserWithPlan(SubscriptionPlan::Starter);
        $creator = createUserWithPlan(SubscriptionPlan::Creator);
        $pro = createUserWithPlan(SubscriptionPlan::Pro);

        expect($starter->currentAccount()->subscriptionLimits()->canUseCustomEmailDomain())->toBeFalse();
        expect($creator->currentAccount()->subscriptionLimits()->canUseCustomEmailDomain())->toBeFalse();
        expect($pro->currentAccount()->subscriptionLimits()->canUseCustomEmailDomain())->toBeTrue();
    });

    test('only Pro users can remove branding', function () {
        $starter = createUserWithPlan(SubscriptionPlan::Starter);
        $creator = createUserWithPlan(SubscriptionPlan::Creator);
        $pro = createUserWithPlan(SubscriptionPlan::Pro);

        expect($starter->currentAccount()->subscriptionLimits()->canRemoveBranding())->toBeFalse();
        expect($creator->currentAccount()->subscriptionLimits()->canRemoveBranding())->toBeFalse();
        expect($pro->currentAccount()->subscriptionLimits()->canRemoveBranding())->toBeTrue();
    });

    test('Starter users cannot access analytics', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);
        $limits = $user->currentAccount()->subscriptionLimits();

        expect($limits->hasAnalytics())->toBeFalse();
    });

    test('Creator and Pro users can access analytics', function () {
        $creator = createUserWithPlan(SubscriptionPlan::Creator);
        $pro = createUserWithPlan(SubscriptionPlan::Pro);

        expect($creator->currentAccount()->subscriptionLimits()->hasAnalytics())->toBeTrue();
        expect($pro->currentAccount()->subscriptionLimits()->hasAnalytics())->toBeTrue();
    });
});

// ============================================
// SOCIAL ACCOUNT LIMITS
// ============================================

describe('Social Account Limit Enforcement', function () {
    test('Starter users limited to 2 social accounts', function () {
        $user = createUserWithPlan(SubscriptionPlan::Starter);
        $brand = $user->currentBrand();

        // Add 2 social connections
        $brand->update([
            'social_connections' => [
                'twitter' => ['access_token' => 'xxx'],
                'facebook' => ['access_token' => 'yyy'],
            ],
        ]);

        $limits = $user->currentAccount()->subscriptionLimits();

        expect($limits->getSocialAccountCount())->toBe(2);
        expect($limits->getSocialAccountLimit())->toBe(2);
        expect($limits->canAddSocialAccount())->toBeFalse();
    });

    test('Creator users can have up to 5 social accounts', function () {
        $user = createUserWithPlan(SubscriptionPlan::Creator);
        $brand = $user->currentBrand();

        // Add 4 social connections
        $brand->update([
            'social_connections' => [
                'twitter' => ['access_token' => 'xxx'],
                'facebook' => ['access_token' => 'yyy'],
                'linkedin' => ['access_token' => 'zzz'],
                'instagram' => ['access_token' => 'aaa'],
            ],
        ]);

        $limits = $user->currentAccount()->subscriptionLimits();

        expect($limits->getSocialAccountCount())->toBe(4);
        expect($limits->getSocialAccountLimit())->toBe(5);
        expect($limits->canAddSocialAccount())->toBeTrue();
        expect($limits->getRemainingSocialAccounts())->toBe(1);
    });

    test('Pro users can have up to 15 social accounts', function () {
        $user = createUserWithPlan(SubscriptionPlan::Pro);

        $limits = $user->currentAccount()->subscriptionLimits();

        expect($limits->getSocialAccountLimit())->toBe(15);
    });
});

// ============================================
// TIER COMPARISON TESTS
// ============================================

describe('Plan Tier Comparisons', function () {
    test('isAtLeast correctly validates access requirements', function () {
        $starter = createUserWithPlan(SubscriptionPlan::Starter);
        $creator = createUserWithPlan(SubscriptionPlan::Creator);
        $pro = createUserWithPlan(SubscriptionPlan::Pro);

        $starterLimits = $starter->currentAccount()->subscriptionLimits();
        $creatorLimits = $creator->currentAccount()->subscriptionLimits();
        $proLimits = $pro->currentAccount()->subscriptionLimits();

        // Starter can access Starter features only
        expect($starterLimits->isAtLeast(SubscriptionPlan::Starter))->toBeTrue();
        expect($starterLimits->isAtLeast(SubscriptionPlan::Creator))->toBeFalse();
        expect($starterLimits->isAtLeast(SubscriptionPlan::Pro))->toBeFalse();

        // Creator can access Starter and Creator features
        expect($creatorLimits->isAtLeast(SubscriptionPlan::Starter))->toBeTrue();
        expect($creatorLimits->isAtLeast(SubscriptionPlan::Creator))->toBeTrue();
        expect($creatorLimits->isAtLeast(SubscriptionPlan::Pro))->toBeFalse();

        // Pro can access all features
        expect($proLimits->isAtLeast(SubscriptionPlan::Starter))->toBeTrue();
        expect($proLimits->isAtLeast(SubscriptionPlan::Creator))->toBeTrue();
        expect($proLimits->isAtLeast(SubscriptionPlan::Pro))->toBeTrue();
    });

    test('users without subscription fail tier checks', function () {
        $user = User::factory()->create();
        $account = Account::factory()->create(['trial_ends_at' => null]);
        $account->users()->attach($user->id, ['role' => 'admin']);

        $limits = $account->subscriptionLimits();

        expect($limits->isAtLeast(SubscriptionPlan::Starter))->toBeFalse();
        expect($limits->isAtLeast(SubscriptionPlan::Creator))->toBeFalse();
        expect($limits->isAtLeast(SubscriptionPlan::Pro))->toBeFalse();
    });

    test('trial users have Starter tier access', function () {
        $user = createUserWithPlan(null, true);
        $limits = $user->currentAccount()->subscriptionLimits();

        expect($limits->isAtLeast(SubscriptionPlan::Starter))->toBeTrue();
        expect($limits->isAtLeast(SubscriptionPlan::Creator))->toBeFalse();
    });
});
