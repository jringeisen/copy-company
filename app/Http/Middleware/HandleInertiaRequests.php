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
                    $brand = $request->user()?->currentBrand();

                    return $brand ? [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'primary_color' => $brand->primary_color,
                    ] : null;
                },
                'brands' => function () use ($request) {
                    $account = $request->user()?->currentAccount();

                    return $account
                        ? $account->brands->map(fn ($brand) => [
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
        ];
    }
}
