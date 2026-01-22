<?php

namespace App\Listeners;

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Services\SesDedicatedIpService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class HandleProSubscription
{
    public function __construct(
        protected SesDedicatedIpService $service
    ) {}

    /**
     * Handle Stripe webhook events for new Pro subscriptions.
     */
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'];

        // Handle both new subscriptions and upgrades to Pro
        if (! in_array($type, ['customer.subscription.created', 'customer.subscription.updated'])) {
            return;
        }

        $subscription = $event->payload['data']['object'];
        $currentPriceId = $subscription['items']['data'][0]['price']['id'] ?? null;

        if (! $currentPriceId) {
            return;
        }

        $currentPlan = SubscriptionPlan::fromPriceId($currentPriceId);

        if ($currentPlan !== SubscriptionPlan::Pro) {
            return;
        }

        // For updates, check if they're upgrading TO Pro
        if ($type === 'customer.subscription.updated') {
            $previousPriceId = $event->payload['data']['previous_attributes']['items']['data'][0]['price']['id'] ?? null;
            if ($previousPriceId) {
                $previousPlan = SubscriptionPlan::fromPriceId($previousPriceId);
                if ($previousPlan === SubscriptionPlan::Pro) {
                    return; // Already Pro, not an upgrade
                }
            }
        }

        $this->handleNewProSubscription($subscription['customer']);
    }

    private function handleNewProSubscription(string $stripeCustomerId): void
    {
        $account = Account::where('stripe_id', $stripeCustomerId)->first();

        if (! $account) {
            Log::warning('Account not found for Pro subscription', ['stripe_customer' => $stripeCustomerId]);

            return;
        }

        // Provision managed dedicated IP access for each brand
        foreach ($account->brands as $brand) {
            if (! $brand->hasDedicatedIp()) {
                $result = $this->service->provisionForProUser($brand);

                Log::info('Provisioned managed dedicated IP access for new Pro brand', [
                    'brand_id' => $brand->id,
                    'account_id' => $account->id,
                    'result' => $result,
                ]);
            }
        }
    }
}
