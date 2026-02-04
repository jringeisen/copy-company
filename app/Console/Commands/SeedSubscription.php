<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionPlan;
use App\Models\User;
use Illuminate\Console\Command;

class SeedSubscription extends Command
{
    protected $signature = 'subscription:seed
                            {email : Email of an existing user}
                            {plan : Subscription plan (starter, creator, pro)}';

    protected $description = 'Seed a subscription for a user to enable local testing of plan-gated features';

    public function handle(): int
    {
        $email = $this->argument('email');
        $plan = $this->argument('plan');

        $subscriptionPlan = SubscriptionPlan::tryFrom($plan);

        if (! $subscriptionPlan) {
            $this->error("Invalid plan: {$plan}. Valid plans: starter, creator, pro");

            return Command::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User not found with email: {$email}");

            return Command::FAILURE;
        }

        $account = $user->accounts()->first();

        if (! $account) {
            $this->error("User {$email} has no associated account.");

            return Command::FAILURE;
        }

        // Only set fallback price IDs if real ones aren't configured
        $fallbacks = [
            'services.stripe.prices.starter_monthly' => 'price_starter_monthly_local',
            'services.stripe.prices.creator_monthly' => 'price_creator_monthly_local',
            'services.stripe.prices.pro_monthly' => 'price_pro_monthly_local',
        ];

        foreach ($fallbacks as $key => $fallback) {
            if (empty(config($key))) {
                config([$key => $fallback]);
            }
        }

        // Ensure the account has a stripe_id
        if (! $account->stripe_id) {
            $account->update(['stripe_id' => 'cus_local_'.uniqid()]);
        }

        // Delete any existing default subscription to allow re-running
        $account->subscriptions()->where('type', 'default')->delete();

        $priceId = $subscriptionPlan->monthlyPriceId();

        // Create the subscription
        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_local_'.$plan.'_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => $priceId,
            'quantity' => 1,
        ]);

        // Create the subscription item
        $subscription->items()->create([
            'stripe_id' => 'si_local_'.uniqid(),
            'stripe_product' => 'prod_local_'.$plan,
            'stripe_price' => $priceId,
            'quantity' => 1,
        ]);

        $this->info("Seeded {$subscriptionPlan->label()} subscription for {$email}.");

        return Command::SUCCESS;
    }
}
