<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionPlan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsSubscribed
{
    /**
     * Handle an incoming request.
     *
     * Ensures the user's account has an active subscription or trial.
     * Optionally can require a specific plan tier.
     *
     * Usage:
     *   Route::middleware('subscribed')->...              // Any active subscription
     *   Route::middleware('subscribed:creator')->...      // Creator plan or higher
     *   Route::middleware('subscribed:pro')->...          // Pro plan required
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $plan = null): Response
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

        // Check if account has any active subscription or trial
        if (! $limits->hasActiveSubscription()) {
            return $this->redirectToSubscribe($request);
        }

        // If a specific plan tier is required, check for it
        if ($plan !== null) {
            $requiredPlan = SubscriptionPlan::tryFrom($plan);

            if ($requiredPlan && ! $limits->isAtLeast($requiredPlan)) {
                return $this->redirectToUpgrade($request, $requiredPlan);
            }
        }

        return $next($request);
    }

    protected function redirectToSubscribe(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subscription required.',
                'redirect' => route('billing.subscribe'),
            ], 402);
        }

        return redirect()->route('billing.subscribe')
            ->with('info', 'Please choose a subscription plan to continue.');
    }

    protected function redirectToUpgrade(Request $request, SubscriptionPlan $requiredPlan): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => "This feature requires the {$requiredPlan->label()} plan or higher.",
                'required_plan' => $requiredPlan->value,
                'redirect' => route('billing.subscribe'),
            ], 402);
        }

        return redirect()->route('billing.subscribe')
            ->with('info', "This feature requires the {$requiredPlan->label()} plan or higher.");
    }
}
