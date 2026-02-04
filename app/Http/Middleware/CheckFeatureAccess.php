<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionPlan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureAccess
{
    /**
     * Handle an incoming request.
     *
     * Gates access to specific features based on subscription plan.
     *
     * Usage:
     *   Route::middleware('feature:custom_domain')->...
     *   Route::middleware('feature:custom_email_domain')->...
     *   Route::middleware('feature:remove_branding')->...
     *   Route::middleware('feature:analytics')->...
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $account = $user->currentAccount();

        if (! $account) {
            return redirect()->route('dashboard')
                ->with('error', 'No account found. Please contact support.');
        }

        $limits = $account->subscriptionLimits();
        $hasAccess = $this->checkFeatureAccess($limits, $feature);

        if (! $hasAccess) {
            $requiredPlan = $limits->getRequiredPlanForFeature($feature);

            return $this->redirectToUpgrade($request, $feature, $requiredPlan);
        }

        return $next($request);
    }

    /**
     * Check if the account has access to the given feature.
     */
    protected function checkFeatureAccess(\App\Services\SubscriptionLimitsService $limits, string $feature): bool
    {
        return match ($feature) {
            'custom_domain' => $limits->canUseCustomDomain(),
            'custom_email_domain' => $limits->canUseCustomEmailDomain(),
            'remove_branding' => $limits->canRemoveBranding(),
            'analytics' => $limits->hasAnalytics(),
            'marketing_strategy' => $limits->canUseMarketingStrategy(),
            default => true,
        };
    }

    protected function redirectToUpgrade(Request $request, string $feature, ?SubscriptionPlan $requiredPlan): Response
    {
        $featureLabel = $this->getFeatureLabel($feature);
        $planLabel = $requiredPlan?->label() ?? 'a higher';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$featureLabel} requires the {$planLabel} plan.",
                'feature' => $feature,
                'required_plan' => $requiredPlan?->value,
                'redirect' => route('billing.subscribe'),
            ], 402);
        }

        return redirect()->route('billing.subscribe')
            ->with('info', "{$featureLabel} requires the {$planLabel} plan. Please upgrade to continue.");
    }

    protected function getFeatureLabel(string $feature): string
    {
        return match ($feature) {
            'custom_domain' => 'Custom domains',
            'custom_email_domain' => 'Custom email domains',
            'remove_branding' => 'Removing Copy Company branding',
            'analytics' => 'Analytics',
            'marketing_strategy' => 'Marketing Strategy',
            default => ucfirst(str_replace('_', ' ', $feature)),
        };
    }
}
