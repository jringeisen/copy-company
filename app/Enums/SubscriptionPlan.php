<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Starter = 'starter';
    case Creator = 'creator';
    case Pro = 'pro';

    /**
     * Get the monthly Stripe price ID for this plan.
     */
    public function monthlyPriceId(): string
    {
        return match ($this) {
            self::Starter => config('services.stripe.prices.starter_monthly'),
            self::Creator => config('services.stripe.prices.creator_monthly'),
            self::Pro => config('services.stripe.prices.pro_monthly'),
        };
    }

    /**
     * Get the annual Stripe price ID for this plan.
     */
    public function annualPriceId(): string
    {
        return match ($this) {
            self::Starter => config('services.stripe.prices.starter_annual'),
            self::Creator => config('services.stripe.prices.creator_annual'),
            self::Pro => config('services.stripe.prices.pro_annual'),
        };
    }

    /**
     * Get the price ID for a given billing interval.
     */
    public function priceId(string $interval = 'monthly'): string
    {
        return $interval === 'annual' ? $this->annualPriceId() : $this->monthlyPriceId();
    }

    /**
     * Resolve a plan from a Stripe price ID.
     */
    public static function fromPriceId(string $priceId): ?self
    {
        $prices = config('services.stripe.prices');

        return match ($priceId) {
            $prices['starter_monthly'], $prices['starter_annual'] => self::Starter,
            $prices['creator_monthly'], $prices['creator_annual'] => self::Creator,
            $prices['pro_monthly'], $prices['pro_annual'] => self::Pro,
            default => null,
        };
    }

    /**
     * Get all Stripe price IDs for this plan.
     */
    public function priceIds(): array
    {
        return [
            $this->monthlyPriceId(),
            $this->annualPriceId(),
        ];
    }

    /**
     * Get the display name for this plan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Starter => 'Starter',
            self::Creator => 'Creator',
            self::Pro => 'Pro',
        };
    }

    /**
     * Get the monthly price in cents.
     */
    public function monthlyPriceCents(): int
    {
        return match ($this) {
            self::Starter => 800,
            self::Creator => 1900,
            self::Pro => 5900,
        };
    }

    /**
     * Get the annual price per month in cents.
     */
    public function annualPricePerMonthCents(): int
    {
        return match ($this) {
            self::Starter => 600,
            self::Creator => 1400,
            self::Pro => 4900,
        };
    }

    /**
     * Get the post limit for this plan (null = unlimited).
     */
    public function postLimit(): ?int
    {
        return match ($this) {
            self::Starter => 5,
            self::Creator, self::Pro => null,
        };
    }

    /**
     * Get the social account limit for this plan.
     */
    public function socialAccountLimit(): int
    {
        return match ($this) {
            self::Starter => 2,
            self::Creator => 5,
            self::Pro => 15,
        };
    }

    /**
     * Get the content sprint limit for this plan (null = unlimited).
     */
    public function contentSprintLimit(): ?int
    {
        return match ($this) {
            self::Starter => 1,
            self::Creator => 10,
            self::Pro => null,
        };
    }

    /**
     * Check if this plan can use custom domains.
     */
    public function canUseCustomDomain(): bool
    {
        return match ($this) {
            self::Starter => false,
            self::Creator, self::Pro => true,
        };
    }

    /**
     * Check if this plan can use custom email domains.
     */
    public function canUseCustomEmailDomain(): bool
    {
        return $this === self::Pro;
    }

    /**
     * Check if this plan can remove branding.
     */
    public function canRemoveBranding(): bool
    {
        return $this === self::Pro;
    }

    /**
     * Check if this plan has analytics access.
     */
    public function hasAnalytics(): bool
    {
        return match ($this) {
            self::Starter => false,
            self::Creator, self::Pro => true,
        };
    }

    /**
     * Get the plan tier level (for comparison).
     */
    public function tier(): int
    {
        return match ($this) {
            self::Starter => 1,
            self::Creator => 2,
            self::Pro => 3,
        };
    }

    /**
     * Check if this plan is higher or equal to another plan.
     */
    public function isAtLeast(self $plan): bool
    {
        return $this->tier() >= $plan->tier();
    }

    /**
     * Check if this plan includes dedicated IP support.
     */
    public function hasDedicatedIpSupport(): bool
    {
        return $this === self::Pro;
    }
}
