<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $brand = $user->currentBrand();

        return Inertia::render('Dashboard', [
            'user' => $user,
            'brand' => $brand,
            'stats' => [
                'postsCount' => $brand?->posts()->count() ?? 0,
                'subscribersCount' => $brand?->active_subscribers_count ?? 0,
                'draftsCount' => $brand?->posts()->draft()->count() ?? 0,
            ],
        ]);
    }
}
