<?php

use App\Enums\MarketingStrategyStatus;
use App\Jobs\GenerateMarketingStrategy;
use App\Models\Brand;
use App\Models\MarketingStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

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

function createUserWithCreatorPlan(): array
{
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    // Create a subscription for Creator plan
    $account = $user->accounts()->first();
    $account->createOrGetStripeCustomer();
    // Simulate Creator plan by creating subscription
    $subscription = $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_creator_monthly',
        'quantity' => 1,
    ]);
    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => 'price_creator_monthly',
        'quantity' => 1,
    ]);

    return [$user, $brand, $account];
}

test('index page loads with strategies', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    $response = $this->actingAs($user)->get(route('strategies.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Strategy/Index')
        ->has('strategies', 1)
    );
});

test('show page loads for completed strategy', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    $response = $this->actingAs($user)->get(route('strategies.show', $strategy));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Strategy/Show')
        ->has('strategy')
        ->where('strategy.status', 'completed')
    );
});

test('converting blog post creates draft post', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    $response = $this->actingAs($user)->post(route('strategies.convert-blog-post', $strategy), [
        'index' => 0,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('posts', [
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    $strategy->refresh();
    expect($strategy->converted_items['blog_posts'])->toContain(0);
});

test('converting social post creates draft social post', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    $response = $this->actingAs($user)->post(route('strategies.convert-social-post', $strategy), [
        'index' => 0,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('social_posts', [
        'brand_id' => $brand->id,
        'status' => 'draft',
        'ai_generated' => true,
    ]);

    $strategy->refresh();
    expect($strategy->converted_items['social_posts'])->toContain(0);
});

test('converting newsletter creates draft post with newsletter flag', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    $response = $this->actingAs($user)->post(route('strategies.convert-newsletter', $strategy));

    $response->assertRedirect();

    $this->assertDatabaseHas('posts', [
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'status' => 'draft',
        'send_as_newsletter' => true,
    ]);

    $strategy->refresh();
    expect($strategy->converted_items['newsletter'])->toBeTrue();
});

test('converting loop content adds items to existing loop', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $loop = $brand->loops()->create([
        'name' => 'Weekly Tips',
        'description' => 'Weekly tips loop',
        'is_active' => true,
        'platforms' => ['instagram'],
    ]);

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->completed()
        ->create([
            'strategy_content' => array_merge(
                MarketingStrategy::factory()->completed()->make()->strategy_content,
                ['loop_content' => [
                    [
                        'loop_id' => $loop->id,
                        'loop_name' => 'Weekly Tips',
                        'suggested_items' => [
                            ['content' => 'Tip one content', 'hashtags' => ['tip']],
                            ['content' => 'Tip two content', 'hashtags' => ['tip']],
                        ],
                    ],
                ]]
            ),
        ]);

    $response = $this->actingAs($user)->post(route('strategies.convert-loop', $strategy), [
        'index' => 0,
        'loop_id' => $loop->id,
    ]);

    $response->assertRedirect();

    expect($loop->items()->count())->toBe(2);

    $strategy->refresh();
    expect($strategy->converted_items['loop_content'])->toContain(0);
});

test('retry resets status and dispatches job', function () {
    Queue::fake();

    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->failed()
        ->create();

    $response = $this->actingAs($user)->post(route('strategies.retry', $strategy));

    $response->assertRedirect();

    $strategy->refresh();
    expect($strategy->status)->toBe(MarketingStrategyStatus::Pending);

    Queue::assertPushed(GenerateMarketingStrategy::class);
});

test('cannot access other brands strategies', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    // Create strategy for a different brand
    $otherBrand = Brand::factory()->create();
    $strategy = MarketingStrategy::factory()
        ->forBrand($otherBrand)
        ->completed()
        ->create();

    $response = $this->actingAs($user)->get(route('strategies.show', $strategy));

    $response->assertForbidden();
});

test('duplicate conversion is tracked correctly', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->completed()
        ->create(['converted_items' => ['blog_posts' => [0]]]);

    // Convert second blog post
    $response = $this->actingAs($user)->post(route('strategies.convert-blog-post', $strategy), [
        'index' => 1,
    ]);

    $response->assertRedirect();

    $strategy->refresh();
    expect($strategy->converted_items['blog_posts'])->toContain(0)->toContain(1);
});

test('starter users are redirected when accessing strategies', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    // Set up Starter plan subscription
    $account = $user->accounts()->first();
    $account->createOrGetStripeCustomer();
    $subscription = $account->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_starter_monthly',
        'quantity' => 1,
    ]);
    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => 'price_starter_monthly',
        'quantity' => 1,
    ]);

    $response = $this->actingAs($user)->get(route('strategies.index'));

    $response->assertRedirect(route('billing.subscribe'));
});

test('cannot convert items from non-completed strategy', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->generating()
        ->create();

    $response = $this->actingAs($user)->post(route('strategies.convert-blog-post', $strategy), [
        'index' => 0,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('cannot retry non-failed strategy', function () {
    [$user, $brand] = createUserWithCreatorPlan();

    $strategy = MarketingStrategy::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    $response = $this->actingAs($user)->post(route('strategies.retry', $strategy));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});
