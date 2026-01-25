<?php

namespace App\Services;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Models\Account;
use App\Models\Dispute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DisputeService
{
    /**
     * Create or update a dispute from Stripe webhook data.
     *
     * @param  array<string, mixed>  $stripeDispute
     */
    public function createOrUpdateFromStripe(array $stripeDispute): ?Dispute
    {
        $stripeId = $stripeDispute['id'] ?? null;
        if (! $stripeId) {
            Log::warning('Dispute webhook missing dispute ID');

            return null;
        }

        $account = $this->findAccountFromCharge($stripeDispute);
        if (! $account) {
            Log::warning('Could not find account for dispute', ['dispute_id' => $stripeId]);

            return null;
        }

        $dispute = Dispute::query()->updateOrCreate(
            ['stripe_dispute_id' => $stripeId],
            [
                'account_id' => $account->id,
                'stripe_charge_id' => $stripeDispute['charge'] ?? null,
                'stripe_payment_intent_id' => $stripeDispute['payment_intent'] ?? null,
                'amount' => $stripeDispute['amount'] ?? 0,
                'currency' => $stripeDispute['currency'] ?? 'usd',
                'status' => $this->mapStripeStatus($stripeDispute['status'] ?? ''),
                'reason' => $this->mapStripeReason($stripeDispute['reason'] ?? ''),
                'evidence_due_at' => isset($stripeDispute['evidence_details']['due_by'])
                    ? Carbon::createFromTimestamp($stripeDispute['evidence_details']['due_by'])
                    : null,
                'disputed_at' => isset($stripeDispute['created'])
                    ? Carbon::createFromTimestamp($stripeDispute['created'])
                    : now(),
                'metadata' => $stripeDispute,
            ]
        );

        return $dispute;
    }

    /**
     * Update dispute status from Stripe.
     *
     * @param  array<string, mixed>  $stripeDispute
     */
    public function updateStatus(Dispute $dispute, array $stripeDispute): Dispute
    {
        $status = $this->mapStripeStatus($stripeDispute['status'] ?? '');

        $dispute->update([
            'status' => $status,
            'metadata' => $stripeDispute,
        ]);

        return $dispute;
    }

    /**
     * Mark dispute as closed.
     *
     * @param  array<string, mixed>  $stripeDispute
     */
    public function markClosed(Dispute $dispute, array $stripeDispute): Dispute
    {
        $status = $this->mapStripeStatus($stripeDispute['status'] ?? '');

        $resolution = null;
        if ($status === DisputeStatus::Won) {
            $resolution = 'won';
        } elseif ($status === DisputeStatus::Lost) {
            $resolution = 'lost';
        } elseif ($status === DisputeStatus::WarningClosed) {
            $resolution = 'warning_closed';
        }

        $dispute->update([
            'status' => $status,
            'resolution' => $resolution,
            'resolved_at' => now(),
            'metadata' => $stripeDispute,
        ]);

        return $dispute;
    }

    /**
     * Mark funds as withdrawn.
     */
    public function markFundsWithdrawn(Dispute $dispute): Dispute
    {
        $dispute->update(['funds_withdrawn' => true]);

        return $dispute;
    }

    /**
     * Mark funds as reinstated.
     */
    public function markFundsReinstated(Dispute $dispute): Dispute
    {
        $dispute->update(['funds_reinstated' => true]);

        return $dispute;
    }

    /**
     * Mark evidence as submitted.
     */
    public function markEvidenceSubmitted(Dispute $dispute): Dispute
    {
        $dispute->update(['evidence_submitted' => true]);

        return $dispute;
    }

    /**
     * Find dispute by Stripe ID.
     */
    public function findByStripeId(string $stripeDisputeId): ?Dispute
    {
        return Dispute::query()->where('stripe_dispute_id', $stripeDisputeId)->first();
    }

    /**
     * Find account from charge data.
     *
     * @param  array<string, mixed>  $stripeDispute
     */
    protected function findAccountFromCharge(array $stripeDispute): ?Account
    {
        $customerId = $stripeDispute['evidence_details']['customer_id'] ?? null;

        if (! $customerId && isset($stripeDispute['charge'])) {
            $chargeId = $stripeDispute['charge'];
            try {
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                $charge = $stripe->charges->retrieve($chargeId);
                $customerId = $charge->customer;
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve charge for dispute', [
                    'charge_id' => $chargeId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (! $customerId) {
            return null;
        }

        return Account::query()->where('stripe_id', $customerId)->first();
    }

    /**
     * Map Stripe status to our DisputeStatus enum.
     */
    protected function mapStripeStatus(string $stripeStatus): DisputeStatus
    {
        return match ($stripeStatus) {
            'warning_needs_response' => DisputeStatus::WarningNeedsResponse,
            'warning_under_review' => DisputeStatus::WarningUnderReview,
            'warning_closed' => DisputeStatus::WarningClosed,
            'needs_response' => DisputeStatus::NeedsResponse,
            'under_review' => DisputeStatus::UnderReview,
            'won' => DisputeStatus::Won,
            'lost' => DisputeStatus::Lost,
            default => DisputeStatus::NeedsResponse,
        };
    }

    /**
     * Map Stripe reason to our DisputeReason enum.
     */
    protected function mapStripeReason(string $stripeReason): DisputeReason
    {
        return match ($stripeReason) {
            'bank_cannot_process' => DisputeReason::BankCannotProcess,
            'check_returned' => DisputeReason::CheckReturned,
            'credit_not_processed' => DisputeReason::CreditNotProcessed,
            'customer_initiated' => DisputeReason::CustomerInitiated,
            'debit_not_authorized' => DisputeReason::DebitNotAuthorized,
            'duplicate' => DisputeReason::Duplicate,
            'fraudulent' => DisputeReason::Fraudulent,
            'general' => DisputeReason::General,
            'incorrect_account_details' => DisputeReason::IncorrectAccountDetails,
            'insufficient_funds' => DisputeReason::InsufficientFunds,
            'product_not_received' => DisputeReason::ProductNotReceived,
            'product_unacceptable' => DisputeReason::ProductUnacceptable,
            'subscription_canceled' => DisputeReason::SubscriptionCanceled,
            'unrecognized' => DisputeReason::Unrecognized,
            default => DisputeReason::General,
        };
    }
}
