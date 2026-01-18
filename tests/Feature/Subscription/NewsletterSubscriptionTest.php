<?php

use App\Enums\PostStatus;
use App\Enums\SubscriptionPlan;
use App\Jobs\ProcessNewsletterSend;
use App\Models\Account;
use App\Models\Brand;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

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

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'posts.create',
        'posts.update',
        'posts.delete',
        'posts.publish',
    ]);
});

function createUserWithSubscription(?SubscriptionPlan $plan = null, bool $onTrial = false): User
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
            'trial_ends_at' => $onTrial ? now()->addDays(14) : null,
        ]);
    }

    $brand = Brand::factory()->forAccount($account)->create();
    $user->update(['current_brand_id' => $brand->id]);

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    return $user;
}

// ============================================
// FREE TRIAL NEWSLETTER RESTRICTIONS
// ============================================

describe('Free Trial Newsletter Restrictions', function () {
    test('trial users cannot send newsletters when publishing', function () {
        $user = createUserWithSubscription(null, true);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'now',
            'publish_to_blog' => true,
            'send_as_newsletter' => true,
            'subject_line' => 'Test Newsletter',
        ]);

        $response->assertSessionHasErrors('send_as_newsletter');
        expect(Post::find($post->id)->status)->toBe(PostStatus::Draft);
        Queue::assertNotPushed(ProcessNewsletterSend::class);
    });

    test('trial users cannot schedule newsletters', function () {
        $user = createUserWithSubscription(null, true);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        $scheduledAt = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'publish_to_blog' => true,
            'send_as_newsletter' => true,
            'subject_line' => 'Scheduled Newsletter',
        ]);

        $response->assertSessionHasErrors('send_as_newsletter');
    });

    test('trial users can publish posts without newsletter', function () {
        $user = createUserWithSubscription(null, true);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'now',
            'publish_to_blog' => true,
            'send_as_newsletter' => false,
        ]);

        $response->assertRedirect(route('posts.index'));
        expect(Post::find($post->id)->status)->toBe(PostStatus::Published);
    });

    test('expired trial users have no active subscription', function () {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'trial_ends_at' => now()->subDay(), // Expired trial
        ]);
        $account->users()->attach($user->id, ['role' => 'admin']);

        $limits = $account->subscriptionLimits();

        // Verify the user's subscription state
        expect($limits->onTrial())->toBeFalse();
        expect($limits->hasActiveSubscription())->toBeFalse();
        expect($limits->getPlan())->toBeNull();
    });
});

// ============================================
// SUBSCRIBED USER NEWSLETTER ACCESS
// ============================================

describe('Subscribed User Newsletter Access', function () {
    test('Starter plan users can send newsletters', function () {
        $user = createUserWithSubscription(SubscriptionPlan::Starter);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        Subscriber::factory()->confirmed()->create(['brand_id' => $brand->id]);

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'now',
            'publish_to_blog' => true,
            'send_as_newsletter' => true,
            'subject_line' => 'Starter Plan Newsletter',
        ]);

        $response->assertRedirect(route('posts.index'));
        expect(Post::find($post->id)->status)->toBe(PostStatus::Published);
        Queue::assertPushed(ProcessNewsletterSend::class);
    });

    test('Creator plan users can send newsletters', function () {
        $user = createUserWithSubscription(SubscriptionPlan::Creator);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        Subscriber::factory()->confirmed()->create(['brand_id' => $brand->id]);

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'now',
            'publish_to_blog' => true,
            'send_as_newsletter' => true,
            'subject_line' => 'Creator Plan Newsletter',
        ]);

        $response->assertRedirect(route('posts.index'));
        expect(Post::find($post->id)->status)->toBe(PostStatus::Published);
        Queue::assertPushed(ProcessNewsletterSend::class);
    });

    test('Pro plan users can send newsletters', function () {
        $user = createUserWithSubscription(SubscriptionPlan::Pro);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        Subscriber::factory()->confirmed()->create(['brand_id' => $brand->id]);

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'now',
            'publish_to_blog' => true,
            'send_as_newsletter' => true,
            'subject_line' => 'Pro Plan Newsletter',
        ]);

        $response->assertRedirect(route('posts.index'));
        expect(Post::find($post->id)->status)->toBe(PostStatus::Published);
        Queue::assertPushed(ProcessNewsletterSend::class);
    });

    test('subscribed users can schedule newsletters', function () {
        $user = createUserWithSubscription(SubscriptionPlan::Creator);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        $scheduledAt = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'publish_to_blog' => true,
            'send_as_newsletter' => true,
            'subject_line' => 'Scheduled Newsletter',
        ]);

        $response->assertRedirect(route('posts.index'));
        expect(Post::find($post->id)->status)->toBe(PostStatus::Scheduled);
    });
});

// ============================================
// SUBSCRIPTION WITH TRIAL PERIOD
// ============================================

describe('Subscription with Trial Period', function () {
    test('users on subscription trial can send newsletters', function () {
        // User with a subscription that has an active trial
        $user = createUserWithSubscription(SubscriptionPlan::Creator, true);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        Subscriber::factory()->confirmed()->create(['brand_id' => $brand->id]);

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'now',
            'publish_to_blog' => true,
            'send_as_newsletter' => true,
            'subject_line' => 'Subscription Trial Newsletter',
        ]);

        $response->assertRedirect(route('posts.index'));
        expect(Post::find($post->id)->status)->toBe(PostStatus::Published);
        Queue::assertPushed(ProcessNewsletterSend::class);
    });

    test('generic trial without subscription cannot send newsletters', function () {
        // User with only a generic trial (no subscription)
        $user = createUserWithSubscription(null, true);
        $brand = $user->currentBrand();
        $post = Post::factory()->forBrand($brand)->draft()->create();

        $response = $this->actingAs($user)->post(route('posts.publish', $post), [
            'schedule_mode' => 'now',
            'publish_to_blog' => true,
            'send_as_newsletter' => true,
            'subject_line' => 'Should Fail',
        ]);

        $response->assertSessionHasErrors('send_as_newsletter');
        Queue::assertNotPushed(ProcessNewsletterSend::class);
    });
});

// ============================================
// NO SUBSCRIPTION/TRIAL
// ============================================

describe('No Subscription or Trial', function () {
    test('users without subscription or trial have no active subscription', function () {
        $user = User::factory()->create();
        $account = Account::factory()->create(['trial_ends_at' => null]);
        $account->users()->attach($user->id, ['role' => 'admin']);

        $limits = $account->subscriptionLimits();

        // Verify no subscription state
        expect($limits->onTrial())->toBeFalse();
        expect($limits->hasActiveSubscription())->toBeFalse();
        expect($limits->getPlan())->toBeNull();
        expect($limits->isOnFreeTrialOnly())->toBeFalse();
    });

    test('users without subscription have no plan limits', function () {
        $user = User::factory()->create();
        $account = Account::factory()->create(['trial_ends_at' => null]);
        $account->users()->attach($user->id, ['role' => 'admin']);

        $limits = $account->subscriptionLimits();

        // All limits should return null or default values
        expect($limits->getPostLimit())->toBeNull();
        expect($limits->getContentSprintLimit())->toBeNull();
        expect($limits->canUseCustomDomain())->toBeFalse();
    });
});
