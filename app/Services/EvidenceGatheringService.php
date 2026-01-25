<?php

namespace App\Services;

use App\Enums\DisputeReason;
use App\Models\Dispute;
use Illuminate\Support\Facades\Log;

class EvidenceGatheringService
{
    /**
     * Gather evidence for a dispute.
     *
     * @return array<string, mixed>
     */
    public function gatherEvidence(Dispute $dispute): array
    {
        $evidence = [];
        $account = $dispute->account;

        if (! $account) {
            return $evidence;
        }

        $admin = $account->admins()->first();

        $evidence['customer_email_address'] = $admin?->email;
        $evidence['customer_name'] = $account->name;

        $subscription = $account->subscription();
        if ($subscription) {
            $evidence['service_date'] = $subscription->created_at?->format('Y-m-d');
        }

        $evidence['product_description'] = $this->getProductDescription($account);
        $evidence['service_documentation'] = $this->getServiceDocumentation($account);
        $evidence['cancellation_policy'] = config('app.url').'/terms#cancellation';
        $evidence['cancellation_policy_disclosure'] = 'Our cancellation policy is clearly displayed on our terms of service page and is shown to all users during checkout.';
        $evidence['refund_policy'] = config('app.url').'/terms#refunds';
        $evidence['refund_policy_disclosure'] = 'Our refund policy is clearly displayed on our terms of service page and is shown to all users during checkout.';

        $evidence = $this->addReasonSpecificEvidence($dispute, $evidence);

        return array_filter($evidence);
    }

    /**
     * Submit evidence to Stripe.
     *
     * @param  array<string, mixed>  $evidence
     */
    public function submitToStripe(Dispute $dispute, array $evidence): bool
    {
        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));

            $stripe->disputes->update(
                $dispute->stripe_dispute_id,
                ['evidence' => $evidence]
            );

            Log::info('Dispute evidence submitted to Stripe', [
                'dispute_id' => $dispute->id,
                'stripe_dispute_id' => $dispute->stripe_dispute_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to submit dispute evidence to Stripe', [
                'dispute_id' => $dispute->id,
                'stripe_dispute_id' => $dispute->stripe_dispute_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get product description based on the account's subscription.
     */
    protected function getProductDescription(\App\Models\Account $account): string
    {
        $description = 'Copy Company - Content management platform for creators. ';
        $description .= 'Features include: blog publishing, newsletter management, social media scheduling, ';
        $description .= 'and AI-powered content generation. ';

        $subscription = $account->subscription();
        if ($subscription) {
            $planName = ucfirst($subscription->stripe_price ?? 'subscription');
            $description .= "Customer is subscribed to the {$planName} plan.";
        }

        return $description;
    }

    /**
     * Get service documentation showing account usage.
     */
    protected function getServiceDocumentation(\App\Models\Account $account): string
    {
        $brands = $account->brands()->count();
        $posts = $account->brands()->withCount('posts')->get()->sum('posts_count');
        $subscribers = $account->brands()->withCount('subscribers')->get()->sum('subscribers_count');

        $doc = "Account Usage Summary:\n";
        $doc .= "- Brands created: {$brands}\n";
        $doc .= "- Posts created: {$posts}\n";
        $doc .= "- Newsletter subscribers: {$subscribers}\n";
        $doc .= "- Account created: {$account->created_at->format('Y-m-d')}\n";

        $subscription = $account->subscription();
        if ($subscription) {
            $doc .= "- Subscription started: {$subscription->created_at->format('Y-m-d')}\n";
        }

        return $doc;
    }

    /**
     * Add reason-specific evidence fields.
     *
     * @param  array<string, mixed>  $evidence
     * @return array<string, mixed>
     */
    protected function addReasonSpecificEvidence(Dispute $dispute, array $evidence): array
    {
        return match ($dispute->reason) {
            DisputeReason::Fraudulent, DisputeReason::Unrecognized => array_merge($evidence, [
                'uncategorized_text' => 'The customer has been using our service actively. '
                    .'Their account shows regular login activity and content creation. '
                    .'The payment was authorized by the cardholder during checkout.',
            ]),
            DisputeReason::ProductNotReceived => array_merge($evidence, [
                'uncategorized_text' => 'This is a digital SaaS product. The customer received '
                    .'immediate access to our platform upon payment. Service was delivered '
                    .'instantaneously as evidenced by their account activity.',
            ]),
            DisputeReason::SubscriptionCanceled => array_merge($evidence, [
                'uncategorized_text' => 'The subscription was active at the time of charge. '
                    .'Customers can cancel at any time through their account settings, '
                    .'which prevents future charges. The disputed charge was valid at the time.',
            ]),
            DisputeReason::Duplicate => array_merge($evidence, [
                'duplicate_charge_explanation' => 'This charge is not a duplicate. '
                    .'Each charge corresponds to a separate billing period. '
                    .'Please review the charge dates for confirmation.',
            ]),
            default => $evidence,
        };
    }
}
