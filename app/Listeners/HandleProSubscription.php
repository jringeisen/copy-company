<?php

namespace App\Listeners;

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Models\User;
use App\Notifications\DedicatedIpAssignmentNeeded;
use App\Services\SesDedicatedIpService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
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

        // Provision dedicated IP resources for each brand
        foreach ($account->brands as $brand) {
            if (! $brand->hasDedicatedIp()) {
                $result = $this->service->provisionDedicatedIp($brand);

                Log::info('Provisioned dedicated IP resources for new Pro brand', [
                    'brand_id' => $brand->id,
                    'account_id' => $account->id,
                    'result' => $result,
                ]);
            }
        }

        // Notify admins that IP assignment is needed
        $this->notifyAdmins($account);
    }

    private function notifyAdmins(Account $account): void
    {
        // Get admin users - customize this based on your admin role system
        $admins = User::where('email', 'like', '%@'.parse_url(config('app.url'), PHP_URL_HOST))->get();

        if ($admins->isEmpty()) {
            Log::warning('No admins found to notify about IP assignment');

            return;
        }

        try {
            Notification::send($admins, new DedicatedIpAssignmentNeeded($account));
        } catch (\Exception $e) {
            Log::error('Failed to send IP assignment notification', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
