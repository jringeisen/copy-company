<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Models\Account;
use App\Models\ContentSprint;
use App\Models\EmailUsage;
use App\Models\Post;
use Carbon\Carbon;

class SubscriptionLimitsService
{
    public function __construct(
        protected Account $account
    ) {}

    /**
     * Get the current subscription plan for the account.
     * Returns null if no active subscription (should trigger subscribe flow).
     * Note: For trial users without a subscription, this returns Starter for limit purposes.
     */
    public function getPlan(): ?SubscriptionPlan
    {
        $subscription = $this->account->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            // Check if on trial without subscription - use Starter limits
            if ($this->account->onTrial()) {
                return SubscriptionPlan::Starter;
            }

            return null;
        }

        // Get the price ID from the subscription
        $priceId = $subscription->stripe_price;

        if (! $priceId) {
            // Multi-price subscription - get from items
            /** @var \Laravel\Cashier\SubscriptionItem|null $item */
            $item = $subscription->items->first();
            $priceId = $item?->stripe_price;
        }

        return $priceId ? SubscriptionPlan::fromPriceId($priceId) : null;
    }

    /**
     * Get the actual subscribed plan (null if only on trial without subscription).
     */
    public function getSubscribedPlan(): ?SubscriptionPlan
    {
        $subscription = $this->account->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            return null;
        }

        $priceId = $subscription->stripe_price;

        if (! $priceId) {
            /** @var \Laravel\Cashier\SubscriptionItem|null $item */
            $item = $subscription->items->first();
            $priceId = $item?->stripe_price;
        }

        return $priceId ? SubscriptionPlan::fromPriceId($priceId) : null;
    }

    /**
     * Check if the account is on a free trial without an active subscription.
     */
    public function isOnFreeTrialOnly(): bool
    {
        return $this->account->onGenericTrial() && ! $this->account->subscribed('default');
    }

    /**
     * Check if the account has an active subscription or trial.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->account->subscribed('default') || $this->account->onTrial();
    }

    /**
     * Check if the account is on trial.
     */
    public function onTrial(): bool
    {
        return $this->account->onTrial('default') || $this->account->onGenericTrial();
    }

    /**
     * Get the trial end date if on trial.
     */
    public function trialEndsAt(): ?Carbon
    {
        $subscription = $this->account->subscription('default');

        if ($subscription && $subscription->onTrial()) {
            return $subscription->trial_ends_at;
        }

        if ($this->account->onGenericTrial()) {
            return $this->account->trial_ends_at;
        }

        return null;
    }

    /**
     * Get the post limit for the current plan.
     */
    public function getPostLimit(): ?int
    {
        return $this->getPlan()?->postLimit();
    }

    /**
     * Get the number of posts created this month across all brands.
     */
    public function getPostsThisMonth(): int
    {
        $brandIds = $this->account->brands()->pluck('id');

        return Post::whereIn('brand_id', $brandIds)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();
    }

    /**
     * Check if the account can create another post this month.
     */
    public function canCreatePost(): bool
    {
        $limit = $this->getPostLimit();

        if ($limit === null) {
            return true;
        }

        return $this->getPostsThisMonth() < $limit;
    }

    /**
     * Get remaining posts that can be created this month.
     */
    public function getRemainingPosts(): ?int
    {
        $limit = $this->getPostLimit();

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->getPostsThisMonth());
    }

    /**
     * Get the content sprint limit for the current plan.
     */
    public function getContentSprintLimit(): ?int
    {
        return $this->getPlan()?->contentSprintLimit();
    }

    /**
     * Get the number of content sprints created this month across all brands.
     */
    public function getContentSprintsThisMonth(): int
    {
        $brandIds = $this->account->brands()->pluck('id');

        return ContentSprint::whereIn('brand_id', $brandIds)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();
    }

    /**
     * Check if the account can create another content sprint this month.
     */
    public function canCreateContentSprint(): bool
    {
        $limit = $this->getContentSprintLimit();

        if ($limit === null) {
            return true;
        }

        return $this->getContentSprintsThisMonth() < $limit;
    }

    /**
     * Get remaining content sprints that can be created this month.
     */
    public function getRemainingContentSprints(): ?int
    {
        $limit = $this->getContentSprintLimit();

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->getContentSprintsThisMonth());
    }

    /**
     * Get the social account limit for the current plan.
     */
    public function getSocialAccountLimit(): int
    {
        return $this->getPlan()?->socialAccountLimit() ?? 2;
    }

    /**
     * Get the total number of connected social accounts across all brands.
     */
    public function getSocialAccountCount(): int
    {
        $count = 0;

        foreach ($this->account->brands as $brand) {
            $connections = $brand->social_connections ?? [];
            $count += count($connections);
        }

        return $count;
    }

    /**
     * Check if the account can add another social account.
     */
    public function canAddSocialAccount(): bool
    {
        return $this->getSocialAccountCount() < $this->getSocialAccountLimit();
    }

    /**
     * Get remaining social account slots.
     */
    public function getRemainingSocialAccounts(): int
    {
        return max(0, $this->getSocialAccountLimit() - $this->getSocialAccountCount());
    }

    /**
     * Check if the current plan allows custom domains.
     */
    public function canUseCustomDomain(): bool
    {
        return $this->getPlan()?->canUseCustomDomain() ?? false;
    }

    /**
     * Check if the current plan allows custom email domains.
     */
    public function canUseCustomEmailDomain(): bool
    {
        return $this->getPlan()?->canUseCustomEmailDomain() ?? false;
    }

    /**
     * Check if the current plan allows removing branding.
     */
    public function canRemoveBranding(): bool
    {
        return $this->getPlan()?->canRemoveBranding() ?? false;
    }

    /**
     * Check if the current plan has analytics access.
     */
    public function hasAnalytics(): bool
    {
        return $this->getPlan()?->hasAnalytics() ?? false;
    }

    /**
     * Check if the current plan includes dedicated IP support.
     */
    public function canUseDedicatedIp(): bool
    {
        return $this->getPlan()?->hasDedicatedIpSupport() ?? false;
    }

    /**
     * Get the number of emails sent this billing period (current month).
     */
    public function getEmailsSentThisMonth(): int
    {
        return (int) EmailUsage::query()
            ->where('account_id', $this->account->id)
            ->where('period_date', '>=', Carbon::now()->startOfMonth())
            ->sum('emails_sent');
    }

    /**
     * Get the estimated email cost for this billing period.
     * Cost is $0.40 per 1,000 emails.
     */
    public function getEstimatedEmailCost(): float
    {
        $emailsSent = $this->getEmailsSentThisMonth();

        // $0.40 per 1,000 emails = $0.0004 per email
        return round($emailsSent * 0.0004, 2);
    }

    /**
     * Get a summary of current usage and limits for display.
     *
     * @return array<string, mixed>
     */
    public function getUsageSummary(): array
    {
        $subscribedPlan = $this->getSubscribedPlan();
        $isOnFreeTrialOnly = $this->isOnFreeTrialOnly();

        // Determine the label to show
        $planLabel = match (true) {
            $isOnFreeTrialOnly => 'Free Trial',
            $subscribedPlan !== null => $subscribedPlan->label(),
            default => 'No Plan',
        };

        return [
            'plan' => $subscribedPlan?->value,
            'plan_label' => $planLabel,
            'on_trial' => $this->onTrial(),
            'is_free_trial' => $isOnFreeTrialOnly,
            'trial_ends_at' => $this->trialEndsAt()?->toISOString(),
            'posts' => [
                'used' => $this->getPostsThisMonth(),
                'limit' => $this->getPostLimit(),
                'remaining' => $this->getRemainingPosts(),
                'can_create' => $this->canCreatePost(),
            ],
            'content_sprints' => [
                'used' => $this->getContentSprintsThisMonth(),
                'limit' => $this->getContentSprintLimit(),
                'remaining' => $this->getRemainingContentSprints(),
                'can_create' => $this->canCreateContentSprint(),
            ],
            'social_accounts' => [
                'used' => $this->getSocialAccountCount(),
                'limit' => $this->getSocialAccountLimit(),
                'remaining' => $this->getRemainingSocialAccounts(),
                'can_add' => $this->canAddSocialAccount(),
            ],
            'features' => [
                'custom_domain' => $this->canUseCustomDomain(),
                'custom_email_domain' => $this->canUseCustomEmailDomain(),
                'remove_branding' => $this->canRemoveBranding(),
                'analytics' => $this->hasAnalytics(),
                'dedicated_ip' => $this->canUseDedicatedIp(),
            ],
            'emails' => [
                'sent' => $this->getEmailsSentThisMonth(),
                'estimated_cost' => $this->getEstimatedEmailCost(),
                'cost_per_thousand' => 0.40,
            ],
        ];
    }

    /**
     * Get the minimum plan required for a specific feature.
     */
    public function getRequiredPlanForFeature(string $feature): ?SubscriptionPlan
    {
        return match ($feature) {
            'custom_domain', 'analytics' => SubscriptionPlan::Creator,
            'custom_email_domain', 'remove_branding', 'dedicated_ip' => SubscriptionPlan::Pro,
            default => SubscriptionPlan::Starter,
        };
    }

    /**
     * Check if the account's plan includes at least the given plan tier.
     */
    public function isAtLeast(SubscriptionPlan $requiredPlan): bool
    {
        $currentPlan = $this->getPlan();

        if (! $currentPlan) {
            return false;
        }

        return $currentPlan->isAtLeast($requiredPlan);
    }
}
