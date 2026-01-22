<?php

namespace App\Listeners;

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Services\SesDedicatedIpService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class HandleSubscriptionDowngrade
{
    public function __construct(
        protected SesDedicatedIpService $service
    ) {}

    /**
     * Handle Stripe webhook events for subscription changes.
     */
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] !== 'customer.subscription.updated') {
            return;
        }

        $subscription = $event->payload['data']['object'];
        $previousPriceId = $event->payload['data']['previous_attributes']['items']['data'][0]['price']['id'] ?? null;
        $currentPriceId = $subscription['items']['data'][0]['price']['id'] ?? null;

        if (! $previousPriceId || ! $currentPriceId) {
            return;
        }

        $previousPlan = SubscriptionPlan::fromPriceId($previousPriceId);
        $currentPlan = SubscriptionPlan::fromPriceId($currentPriceId);

        // Check if downgrading from Pro to a lower plan
        if ($previousPlan === SubscriptionPlan::Pro && $currentPlan !== SubscriptionPlan::Pro) {
            $this->handleProDowngrade($subscription['customer']);
        }
    }

    private function handleProDowngrade(string $stripeCustomerId): void
    {
        $account = Account::where('stripe_id', $stripeCustomerId)->first();

        if (! $account) {
            Log::warning('Account not found for Pro downgrade', ['stripe_customer' => $stripeCustomerId]);

            return;
        }

        // Release dedicated IPs for all brands in this account
        foreach ($account->brands as $brand) {
            if ($brand->hasDedicatedIp()) {
                $result = $this->service->releaseProUser($brand, null, 'subscription_downgrade');

                Log::info('Released dedicated IP on subscription downgrade', [
                    'brand_id' => $brand->id,
                    'account_id' => $account->id,
                    'result' => $result,
                ]);
            }
        }
    }
}
