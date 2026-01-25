<?php

namespace App\Enums;

enum DisputeReason: string
{
    case BankCannotProcess = 'bank_cannot_process';
    case CheckReturned = 'check_returned';
    case CreditNotProcessed = 'credit_not_processed';
    case CustomerInitiated = 'customer_initiated';
    case DebitNotAuthorized = 'debit_not_authorized';
    case Duplicate = 'duplicate';
    case Fraudulent = 'fraudulent';
    case General = 'general';
    case IncorrectAccountDetails = 'incorrect_account_details';
    case InsufficientFunds = 'insufficient_funds';
    case ProductNotReceived = 'product_not_received';
    case ProductUnacceptable = 'product_unacceptable';
    case SubscriptionCanceled = 'subscription_canceled';
    case Unrecognized = 'unrecognized';

    /**
     * Get the display label for this reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::BankCannotProcess => 'Bank Cannot Process',
            self::CheckReturned => 'Check Returned',
            self::CreditNotProcessed => 'Credit Not Processed',
            self::CustomerInitiated => 'Customer Initiated',
            self::DebitNotAuthorized => 'Debit Not Authorized',
            self::Duplicate => 'Duplicate Charge',
            self::Fraudulent => 'Fraudulent',
            self::General => 'General',
            self::IncorrectAccountDetails => 'Incorrect Account Details',
            self::InsufficientFunds => 'Insufficient Funds',
            self::ProductNotReceived => 'Product Not Received',
            self::ProductUnacceptable => 'Product Unacceptable',
            self::SubscriptionCanceled => 'Subscription Canceled',
            self::Unrecognized => 'Unrecognized',
        };
    }

    /**
     * Get the recommended evidence type for this dispute reason.
     *
     * @return array<string>
     */
    public function recommendedEvidence(): array
    {
        return match ($this) {
            self::Fraudulent, self::Unrecognized => [
                'customer_signature',
                'customer_communication',
                'service_documentation',
            ],
            self::ProductNotReceived => [
                'shipping_documentation',
                'shipping_tracking_number',
                'service_date',
            ],
            self::ProductUnacceptable => [
                'customer_communication',
                'refund_policy',
                'refund_policy_disclosure',
            ],
            self::SubscriptionCanceled => [
                'cancellation_policy',
                'cancellation_policy_disclosure',
                'customer_communication',
            ],
            self::Duplicate => [
                'duplicate_charge_documentation',
                'duplicate_charge_explanation',
            ],
            self::CreditNotProcessed => [
                'refund_policy',
                'refund_refusal_explanation',
            ],
            default => [
                'customer_communication',
                'service_documentation',
            ],
        };
    }
}
