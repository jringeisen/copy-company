<?php

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable Stripe webhook signature verification for testing
    config(['cashier.webhook.secret' => null]);

    // Set up fake Stripe price IDs
    config([
        'services.stripe.prices.starter_monthly' => 'price_starter_monthly',
        'services.stripe.prices.starter_annual' => 'price_starter_annual',
        'services.stripe.prices.creator_monthly' => 'price_creator_monthly',
        'services.stripe.prices.creator_annual' => 'price_creator_annual',
        'services.stripe.prices.pro_monthly' => 'price_pro_monthly',
        'services.stripe.prices.pro_annual' => 'price_pro_annual',
    ]);
});

function buildWebhookPayload(string $type, array $data = []): array
{
    return [
        'id' => 'evt_'.uniqid(),
        'type' => $type,
        'data' => [
            'object' => array_merge([
                'id' => 'obj_'.uniqid(),
            ], $data),
        ],
        'livemode' => false,
        'created' => time(),
    ];
}

// ============================================
// SUBSCRIPTION CREATION VIA WEBHOOK
// ============================================

describe('Subscription Creation via Webhook', function () {
    test('subscription created webhook is acknowledged', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_create']);

        $payload = buildWebhookPayload('customer.subscription.created', [
            'id' => 'sub_new_subscription',
            'customer' => 'cus_test_create',
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
            'items' => [
                'data' => [
                    [
                        'id' => 'si_item123',
                        'price' => [
                            'id' => 'price_creator_monthly',
                            'product' => 'prod_creator',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->postJson('/webhooks/stripe', $payload);

        // Webhook is acknowledged
        $response->assertOk();
    });

    test('subscription with trial status is correctly stored', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_trial']);

        $trialEnd = now()->addDays(14);

        // Pre-create subscription (as would happen via checkout)
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_trial_subscription',
            'stripe_status' => 'trialing',
            'stripe_price' => 'price_starter_monthly',
            'trial_ends_at' => $trialEnd,
        ]);

        expect($subscription->onTrial())->toBeTrue();
        expect($subscription->stripe_status)->toBe('trialing');
    });
});

// ============================================
// SUBSCRIPTION UPDATE VIA WEBHOOK
// ============================================

describe('Subscription Update via Webhook', function () {
    test('subscription update webhook updates status in database', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_update']);

        // Create initial subscription
        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_to_update',
            'stripe_status' => 'trialing',
            'stripe_price' => 'price_starter_monthly',
        ]);

        $payload = buildWebhookPayload('customer.subscription.updated', [
            'id' => 'sub_to_update',
            'customer' => 'cus_test_update',
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
            'items' => [
                'data' => [
                    [
                        'id' => 'si_updated_item',
                        'price' => [
                            'id' => 'price_starter_monthly',
                            'product' => 'prod_starter',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->postJson('/webhooks/stripe', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_to_update',
            'stripe_status' => 'active',
        ]);
    });

    test('subscription update webhook handles plan change', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_upgrade']);

        // Create initial Starter subscription
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_upgrade',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
        ]);

        $subscription->items()->create([
            'stripe_id' => 'si_old_item',
            'stripe_product' => 'prod_starter',
            'stripe_price' => 'price_starter_monthly',
            'quantity' => 1,
        ]);

        // Webhook with new Creator plan
        $payload = buildWebhookPayload('customer.subscription.updated', [
            'id' => 'sub_upgrade',
            'customer' => 'cus_test_upgrade',
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
            'items' => [
                'data' => [
                    [
                        'id' => 'si_new_item',
                        'price' => [
                            'id' => 'price_creator_monthly',
                            'product' => 'prod_creator',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->postJson('/webhooks/stripe', $payload);

        $response->assertOk();

        // Check subscription item was updated
        $this->assertDatabaseHas('subscription_items', [
            'subscription_id' => $subscription->id,
            'stripe_price' => 'price_creator_monthly',
        ]);
    });
});

// ============================================
// SUBSCRIPTION DELETION VIA WEBHOOK
// ============================================

describe('Subscription Deletion via Webhook', function () {
    test('subscription deleted webhook updates status to canceled', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_delete']);

        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_to_delete',
            'stripe_status' => 'active',
            'stripe_price' => 'price_creator_monthly',
        ]);

        $payload = buildWebhookPayload('customer.subscription.deleted', [
            'id' => 'sub_to_delete',
            'customer' => 'cus_test_delete',
            'status' => 'canceled',
            'canceled_at' => now()->timestamp,
            'items' => [
                'data' => [
                    [
                        'id' => 'si_deleted_item',
                        'price' => [
                            'id' => 'price_creator_monthly',
                            'product' => 'prod_creator',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->postJson('/webhooks/stripe', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_to_delete',
            'stripe_status' => 'canceled',
        ]);

        expect($account->fresh()->subscribed('default'))->toBeFalse();
    });

    test('subscription with grace period maintains access', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_grace']);

        $gracePeriodEnd = now()->addWeeks(2);

        // Create subscription that's cancelled but on grace period
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_grace_period',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_monthly',
            'ends_at' => $gracePeriodEnd,
        ]);

        expect($subscription->canceled())->toBeTrue();
        expect($subscription->onGracePeriod())->toBeTrue();
        expect($subscription->valid())->toBeTrue();
    });
});

// ============================================
// PAYMENT FAILURE HANDLING
// ============================================

describe('Payment Failure Handling', function () {
    test('invoice payment failed updates subscription status to past_due', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_payment_fail']);

        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_payment_fail',
            'stripe_status' => 'active',
            'stripe_price' => 'price_creator_monthly',
        ]);

        // First, simulate the subscription status update that would come from Stripe
        $payload = buildWebhookPayload('customer.subscription.updated', [
            'id' => 'sub_payment_fail',
            'customer' => 'cus_test_payment_fail',
            'status' => 'past_due',
            'current_period_start' => now()->subMonth()->timestamp,
            'current_period_end' => now()->timestamp,
            'items' => [
                'data' => [
                    [
                        'id' => 'si_past_due_item',
                        'price' => [
                            'id' => 'price_creator_monthly',
                            'product' => 'prod_creator',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->postJson('/webhooks/stripe', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_payment_fail',
            'stripe_status' => 'past_due',
        ]);
    });

    test('subscription with incomplete status after payment failure', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_incomplete']);

        $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_incomplete',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
        ]);

        $payload = buildWebhookPayload('customer.subscription.updated', [
            'id' => 'sub_incomplete',
            'customer' => 'cus_test_incomplete',
            'status' => 'incomplete',
            'items' => [
                'data' => [
                    [
                        'id' => 'si_incomplete_item',
                        'price' => [
                            'id' => 'price_starter_monthly',
                            'product' => 'prod_starter',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->postJson('/webhooks/stripe', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'stripe_id' => 'sub_incomplete',
            'stripe_status' => 'incomplete',
        ]);

        // Subscription should not be valid when incomplete
        $subscription = $account->fresh()->subscription('default');
        expect($subscription->valid())->toBeFalse();
    });
});

// ============================================
// EDGE CASES
// ============================================

describe('Webhook Edge Cases', function () {
    test('webhook for non-existent customer returns ok', function () {
        $payload = buildWebhookPayload('customer.subscription.created', [
            'id' => 'sub_orphan',
            'customer' => 'cus_does_not_exist',
            'status' => 'active',
            'items' => [
                'data' => [
                    [
                        'id' => 'si_orphan_item',
                        'price' => [
                            'id' => 'price_starter_monthly',
                            'product' => 'prod_starter',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        $response = $this->postJson('/webhooks/stripe', $payload);

        // Should still return OK (webhook acknowledged)
        $response->assertOk();

        // But no subscription should be created
        $this->assertDatabaseMissing('subscriptions', [
            'stripe_id' => 'sub_orphan',
        ]);
    });

    test('webhook handles unhandled event types gracefully', function () {
        $payload = buildWebhookPayload('payment_intent.succeeded', [
            'id' => 'pi_test123',
            'amount' => 1999,
            'currency' => 'usd',
        ]);

        $response = $this->postJson('/webhooks/stripe', $payload);

        // Unhandled events should still return OK
        $response->assertOk();
    });

    test('duplicate webhook is handled idempotently', function () {
        $account = Account::factory()->create();
        $account->update(['stripe_id' => 'cus_test_duplicate']);

        $payload = buildWebhookPayload('customer.subscription.created', [
            'id' => 'sub_duplicate',
            'customer' => 'cus_test_duplicate',
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
            'items' => [
                'data' => [
                    [
                        'id' => 'si_dup_item',
                        'price' => [
                            'id' => 'price_starter_monthly',
                            'product' => 'prod_starter',
                        ],
                        'quantity' => 1,
                    ],
                ],
            ],
        ]);

        // Send webhook twice
        $this->postJson('/webhooks/stripe', $payload)->assertOk();
        $this->postJson('/webhooks/stripe', $payload)->assertOk();

        // Should only have one subscription
        expect($account->fresh()->subscriptions()->count())->toBe(1);
    });
});
