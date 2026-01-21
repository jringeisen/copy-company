<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            // CSRF token for traditional form submissions
            'csrf_token' => csrf_token(),

            // Flash messages
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'message' => fn () => $request->session()->get('message'),
            ],

            // Authenticated user
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
                'brand' => function () use ($request) {
                    /** @var \App\Models\User|null $user */
                    $user = $request->user();
                    $brand = $user?->currentBrand();

                    return $brand ? [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'primary_color' => $brand->primary_color,
                    ] : null;
                },
                'brands' => function () use ($request) {
                    /** @var \App\Models\User|null $user */
                    $user = $request->user();
                    $account = $user?->currentAccount();

                    return $account
                        ? $account->brands->map(fn (\App\Models\Brand $brand) => [
                            'id' => $brand->id,
                            'name' => $brand->name,
                            'slug' => $brand->slug,
                            'primary_color' => $brand->primary_color,
                        ])->toArray()
                        : [];
                },
                'permissions' => fn () => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')->toArray()
                    : [],
            ],

            // Subscription data for current account
            'subscription' => function () use ($request) {
                /** @var \App\Models\User|null $user */
                $user = $request->user();
                $account = $user?->currentAccount();

                if (! $account) {
                    return null;
                }

                $limits = $account->subscriptionLimits();

                return [
                    'plan' => $limits->getPlan()?->value,
                    'plan_label' => $limits->getPlan()?->label() ?? 'No Plan',
                    'on_trial' => $limits->onTrial(),
                    'is_free_trial_only' => $limits->isOnFreeTrialOnly(),
                    'is_subscribed' => ! $limits->isOnFreeTrialOnly() && $limits->hasActiveSubscription(),
                    'has_subscription' => $limits->hasActiveSubscription(),
                    'can_create_post' => $limits->canCreatePost(),
                    'can_create_sprint' => $limits->canCreateContentSprint(),
                    'can_add_social' => $limits->canAddSocialAccount(),
                    'can_send_newsletter' => ! $limits->isOnFreeTrialOnly(),
                    'features' => [
                        'custom_domain' => $limits->canUseCustomDomain(),
                        'custom_email_domain' => $limits->canUseCustomEmailDomain(),
                        'remove_branding' => $limits->canRemoveBranding(),
                        'analytics' => $limits->hasAnalytics(),
                        'dedicated_ip' => $limits->canUseDedicatedIp(),
                    ],
                ];
            },

            // Dedicated IP status for current brand
            'dedicatedIp' => function () use ($request) {
                /** @var \App\Models\User|null $user */
                $user = $request->user();
                $brand = $user?->currentBrand();

                if (! $brand) {
                    return null;
                }

                return $brand->getDedicatedIpInfo();
            },
        ];
    }
}
