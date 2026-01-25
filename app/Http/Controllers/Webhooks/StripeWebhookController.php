<?php

namespace App\Http\Controllers\Webhooks;

use App\Jobs\ProcessDisputeClosed;
use App\Jobs\ProcessDisputeCreated;
use App\Jobs\ProcessDisputeUpdated;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends WebhookController
{
    /**
     * Handle invoice payment failed.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        Log::warning('Stripe invoice payment failed', [
            'customer' => $payload['data']['object']['customer'] ?? null,
            'invoice_id' => $payload['data']['object']['id'] ?? null,
        ]);

        // You could notify the user here via email
        // or trigger other business logic

        return $this->successMethod();
    }

    /**
     * Handle customer subscription created.
     * Extends parent to add custom logging.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        Log::info('Stripe subscription created', [
            'customer' => $payload['data']['object']['customer'] ?? null,
            'subscription_id' => $payload['data']['object']['id'] ?? null,
            'status' => $payload['data']['object']['status'] ?? null,
        ]);

        return parent::handleCustomerSubscriptionCreated($payload);
    }

    /**
     * Handle customer subscription deleted.
     * Extends parent to add custom logging.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        Log::info('Stripe subscription deleted', [
            'customer' => $payload['data']['object']['customer'] ?? null,
            'subscription_id' => $payload['data']['object']['id'] ?? null,
        ]);

        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Handle customer subscription trial will end.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCustomerSubscriptionTrialWillEnd(array $payload): Response
    {
        Log::info('Stripe subscription trial ending soon', [
            'customer' => $payload['data']['object']['customer'] ?? null,
            'subscription_id' => $payload['data']['object']['id'] ?? null,
            'trial_end' => $payload['data']['object']['trial_end'] ?? null,
        ]);

        // You could notify the user here that their trial is ending

        return $this->successMethod();
    }

    /**
     * Handle checkout session completed.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        Log::info('Stripe checkout session completed', [
            'session_id' => $payload['data']['object']['id'] ?? null,
            'customer' => $payload['data']['object']['customer'] ?? null,
            'mode' => $payload['data']['object']['mode'] ?? null,
        ]);

        return $this->successMethod();
    }

    /**
     * Handle charge dispute created.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleChargeDisputeCreated(array $payload): Response
    {
        Log::info('Stripe dispute created', [
            'dispute_id' => $payload['data']['object']['id'] ?? null,
            'charge_id' => $payload['data']['object']['charge'] ?? null,
            'amount' => $payload['data']['object']['amount'] ?? null,
            'reason' => $payload['data']['object']['reason'] ?? null,
        ]);

        ProcessDisputeCreated::dispatch($payload);

        return $this->successMethod();
    }

    /**
     * Handle charge dispute updated.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleChargeDisputeUpdated(array $payload): Response
    {
        Log::info('Stripe dispute updated', [
            'dispute_id' => $payload['data']['object']['id'] ?? null,
            'status' => $payload['data']['object']['status'] ?? null,
        ]);

        ProcessDisputeUpdated::dispatch($payload);

        return $this->successMethod();
    }

    /**
     * Handle charge dispute closed.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleChargeDisputeClosed(array $payload): Response
    {
        Log::info('Stripe dispute closed', [
            'dispute_id' => $payload['data']['object']['id'] ?? null,
            'status' => $payload['data']['object']['status'] ?? null,
        ]);

        ProcessDisputeClosed::dispatch($payload);

        return $this->successMethod();
    }

    /**
     * Handle charge dispute funds withdrawn.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleChargeDisputeFundsWithdrawn(array $payload): Response
    {
        Log::info('Stripe dispute funds withdrawn', [
            'dispute_id' => $payload['data']['object']['id'] ?? null,
            'amount' => $payload['data']['object']['amount'] ?? null,
        ]);

        $stripeDisputeId = $payload['data']['object']['id'] ?? null;
        if ($stripeDisputeId) {
            $disputeService = app(DisputeService::class);
            $dispute = $disputeService->findByStripeId($stripeDisputeId);
            if ($dispute) {
                $disputeService->markFundsWithdrawn($dispute);
                $dispute->recordEvent('dispute.funds_withdrawn', $payload['data']['object']);
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle charge dispute funds reinstated.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function handleChargeDisputeFundsReinstated(array $payload): Response
    {
        Log::info('Stripe dispute funds reinstated', [
            'dispute_id' => $payload['data']['object']['id'] ?? null,
            'amount' => $payload['data']['object']['amount'] ?? null,
        ]);

        $stripeDisputeId = $payload['data']['object']['id'] ?? null;
        if ($stripeDisputeId) {
            $disputeService = app(DisputeService::class);
            $dispute = $disputeService->findByStripeId($stripeDisputeId);
            if ($dispute) {
                $disputeService->markFundsReinstated($dispute);
                $dispute->recordEvent('dispute.funds_reinstated', $payload['data']['object']);
            }
        }

        return $this->successMethod();
    }
}
