<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable Stripe webhook signature verification for testing
    config(['cashier.webhook.secret' => null]);
});

function buildStripePayload(string $type, array $data = []): array
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

// ==========================================
// INVOICE PAYMENT FAILED TESTS
// ==========================================

test('handleInvoicePaymentFailed logs warning with customer and invoice id', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('Stripe invoice payment failed', Mockery::on(function ($data) {
            return $data['customer'] === 'cus_test123'
                && $data['invoice_id'] === 'inv_test456';
        }));

    $payload = buildStripePayload('invoice.payment_failed', [
        'id' => 'inv_test456',
        'customer' => 'cus_test123',
    ]);

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});

test('handleInvoicePaymentFailed handles missing customer gracefully', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('Stripe invoice payment failed', Mockery::on(function ($data) {
            return $data['customer'] === null
                && $data['invoice_id'] === 'inv_test789';
        }));

    $payload = buildStripePayload('invoice.payment_failed', [
        'id' => 'inv_test789',
        // No customer field
    ]);

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});

// ==========================================
// SUBSCRIPTION CREATED TESTS
// ==========================================

test('handleCustomerSubscriptionCreated logs info with subscription details', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Stripe subscription created', Mockery::on(function ($data) {
            return $data['customer'] === 'cus_created123'
                && $data['subscription_id'] === 'sub_created456'
                && $data['status'] === 'active';
        }));

    $payload = buildStripePayload('customer.subscription.created', [
        'id' => 'sub_created456',
        'customer' => 'cus_created123',
        'status' => 'active',
        'items' => [
            'data' => [
                ['price' => ['id' => 'price_test']],
            ],
        ],
    ]);

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});

// ==========================================
// SUBSCRIPTION DELETED TESTS
// ==========================================

test('handleCustomerSubscriptionDeleted logs info with subscription details', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Stripe subscription deleted', Mockery::on(function ($data) {
            return $data['customer'] === 'cus_deleted123'
                && $data['subscription_id'] === 'sub_deleted456';
        }));

    $payload = buildStripePayload('customer.subscription.deleted', [
        'id' => 'sub_deleted456',
        'customer' => 'cus_deleted123',
        'items' => [
            'data' => [
                ['price' => ['id' => 'price_test']],
            ],
        ],
    ]);

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});

// ==========================================
// TRIAL WILL END TESTS
// ==========================================

test('handleCustomerSubscriptionTrialWillEnd logs info with trial end date', function () {
    $trialEnd = now()->addDays(3)->timestamp;

    Log::shouldReceive('info')
        ->once()
        ->with('Stripe subscription trial ending soon', Mockery::on(function ($data) use ($trialEnd) {
            return $data['customer'] === 'cus_trial123'
                && $data['subscription_id'] === 'sub_trial456'
                && $data['trial_end'] === $trialEnd;
        }));

    $payload = buildStripePayload('customer.subscription.trial_will_end', [
        'id' => 'sub_trial456',
        'customer' => 'cus_trial123',
        'trial_end' => $trialEnd,
    ]);

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});

// ==========================================
// CHECKOUT SESSION COMPLETED TESTS
// ==========================================

test('handleCheckoutSessionCompleted logs info with session details', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Stripe checkout session completed', Mockery::on(function ($data) {
            return $data['session_id'] === 'cs_test123'
                && $data['customer'] === 'cus_checkout456'
                && $data['mode'] === 'subscription';
        }));

    $payload = buildStripePayload('checkout.session.completed', [
        'id' => 'cs_test123',
        'customer' => 'cus_checkout456',
        'mode' => 'subscription',
    ]);

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});

test('handleCheckoutSessionCompleted handles payment mode', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Stripe checkout session completed', Mockery::on(function ($data) {
            return $data['mode'] === 'payment';
        }));

    $payload = buildStripePayload('checkout.session.completed', [
        'id' => 'cs_payment123',
        'customer' => 'cus_payment456',
        'mode' => 'payment',
    ]);

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});

// ==========================================
// EDGE CASES
// ==========================================

test('webhook returns 200 for unhandled event types', function () {
    $payload = buildStripePayload('charge.succeeded', [
        'id' => 'ch_test123',
        'amount' => 1000,
    ]);

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});

test('webhook returns 200 for empty payload fields', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('Stripe checkout session completed', Mockery::on(function ($data) {
            return $data['session_id'] === null
                && $data['customer'] === null
                && $data['mode'] === null;
        }));

    $payload = [
        'id' => 'evt_'.uniqid(),
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [],
        ],
        'livemode' => false,
        'created' => time(),
    ];

    $response = $this->postJson('/webhooks/stripe', $payload);

    $response->assertOk();
});
