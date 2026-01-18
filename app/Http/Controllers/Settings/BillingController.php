<?php

namespace App\Http\Controllers\Settings;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BillingController extends Controller
{
    /**
     * Display the billing settings page.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $account = $user->currentAccount();

        if (! $account) {
            return redirect()->route('dashboard')
                ->with('error', 'No account found.');
        }

        $limits = $account->subscriptionLimits();
        $subscription = $account->subscription('default');

        // Build invoices data
        $invoices = [];
        if ($account->hasStripeId()) {
            $invoices = $account->invoices()->map(function (\Laravel\Cashier\Invoice $invoice) {
                return [
                    'id' => $invoice->asStripeInvoice()->id,
                    'date' => $invoice->date()->format('M j, Y'),
                    'total' => $invoice->total(),
                    'status' => $invoice->asStripeInvoice()->status,
                    'download_url' => route('billing.invoice.download', $invoice->asStripeInvoice()->id),
                ];
            })->toArray();
        }

        return Inertia::render('Settings/Billing', [
            'usage' => $limits->getUsageSummary(),
            'subscription' => $subscription ? [
                'stripe_status' => $subscription->stripe_status,
                'ends_at' => $subscription->ends_at?->format('M j, Y'),
                'on_grace_period' => $subscription->onGracePeriod(),
                'canceled' => $subscription->canceled(),
            ] : null,
            'invoices' => $invoices,
            'has_payment_method' => $account->hasDefaultPaymentMethod(),
        ]);
    }

    /**
     * Display the plan selection / subscribe page.
     */
    public function subscribe(Request $request): Response|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $account = $user->currentAccount();

        if (! $account) {
            return redirect()->route('dashboard')
                ->with('error', 'No account found.');
        }

        $limits = $account->subscriptionLimits();
        $currentPlan = $limits->getSubscribedPlan(); // Use actual subscribed plan, not trial

        $plans = collect(SubscriptionPlan::cases())->map(function (SubscriptionPlan $plan) use ($currentPlan) {
            return [
                'value' => $plan->value,
                'label' => $plan->label(),
                'monthly_price' => $plan->monthlyPriceCents() / 100,
                'annual_price_per_month' => $plan->annualPricePerMonthCents() / 100,
                'post_limit' => $plan->postLimit(),
                'social_account_limit' => $plan->socialAccountLimit(),
                'content_sprint_limit' => $plan->contentSprintLimit(),
                'features' => [
                    'custom_domain' => $plan->canUseCustomDomain(),
                    'custom_email_domain' => $plan->canUseCustomEmailDomain(),
                    'remove_branding' => $plan->canRemoveBranding(),
                    'analytics' => $plan->hasAnalytics(),
                ],
                'is_current' => $currentPlan === $plan,
                'is_higher' => $currentPlan && $plan->tier() > $currentPlan->tier(),
            ];
        })->toArray();

        return Inertia::render('Billing/Subscribe', [
            'plans' => $plans,
            'current_plan' => $currentPlan?->value,
            'on_trial' => $limits->onTrial(),
            'trial_ends_at' => $limits->trialEndsAt()?->format('M j, Y'),
        ]);
    }

    /**
     * Redirect to Stripe Checkout for subscription.
     */
    public function checkout(Request $request): HttpResponse|\Laravel\Cashier\Checkout
    {
        $request->validate([
            'plan' => ['required', 'string', 'in:starter,creator,pro'],
            'interval' => ['required', 'string', 'in:monthly,annual'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $account = $user->currentAccount();

        if (! $account) {
            return back()->with('error', 'No account found.');
        }

        $plan = SubscriptionPlan::from($request->input('plan'));
        $priceId = $plan->priceId($request->input('interval'));

        try {
            // If already subscribed, swap to new plan
            if ($account->subscribed('default')) {
                $account->subscription('default')->swap($priceId);

                return redirect()->route('settings.billing')
                    ->with('success', "Successfully changed to the {$plan->label()} plan!");
            }

            // Create new subscription via Stripe Checkout
            return $account
                ->newSubscription('default', $priceId)
                ->trialDays(14)
                ->checkout([
                    'success_url' => route('settings.billing').'?checkout=success',
                    'cancel_url' => route('billing.subscribe').'?checkout=canceled',
                    'allow_promotion_codes' => true,
                ]);
        } catch (IncompletePayment $e) {
            return redirect()->route('cashier.payment', [
                $e->payment->asStripePaymentIntent()->id,
                'redirect' => route('settings.billing'),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to start checkout: '.$e->getMessage());
        }
    }

    /**
     * Redirect to Stripe Customer Portal.
     */
    public function portal(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $account = $user->currentAccount();

        if (! $account) {
            return redirect()->route('dashboard')
                ->with('error', 'No account found.');
        }

        return redirect($account->billingPortalUrl(route('settings.billing')));
    }

    /**
     * Download an invoice PDF.
     */
    public function downloadInvoice(Request $request, string $invoiceId): HttpResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $account = $user->currentAccount();

        if (! $account) {
            abort(404);
        }

        return $account->downloadInvoice($invoiceId, [
            'vendor' => 'Copy Company',
            'product' => 'Subscription',
        ]);
    }

    /**
     * Resume a canceled subscription.
     */
    public function resume(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $account = $user->currentAccount();

        if (! $account) {
            return redirect()->route('dashboard')
                ->with('error', 'No account found.');
        }

        $subscription = $account->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();

            return redirect()->route('settings.billing')
                ->with('success', 'Your subscription has been resumed.');
        }

        return redirect()->route('settings.billing')
            ->with('error', 'Unable to resume subscription.');
    }

    /**
     * Cancel the current subscription.
     */
    public function cancel(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $account = $user->currentAccount();

        if (! $account) {
            return redirect()->route('dashboard')
                ->with('error', 'No account found.');
        }

        $subscription = $account->subscription('default');

        if ($subscription && $subscription->valid()) {
            $subscription->cancel();

            return redirect()->route('settings.billing')
                ->with('success', 'Your subscription has been canceled. You can continue using the service until the end of your billing period.');
        }

        return redirect()->route('settings.billing')
            ->with('error', 'No active subscription to cancel.');
    }
}
