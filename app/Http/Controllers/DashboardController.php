<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use HasBrandAuthorization;

    public function index(): Response
    {
        $user = auth()->user();
        $brand = $this->currentBrand();

        // Eager load counts in a single query
        if ($brand) {
            $brand = Brand::where('id', $brand->id)
                ->withCount([
                    'posts',
                    'posts as drafts_count' => fn (Builder $query) => $query->where('status', PostStatus::Draft),
                    'subscribers as confirmed_subscribers_count' => fn (Builder $query) => $query->confirmed(),
                ])
                ->first();
        }

        return Inertia::render('Dashboard', [
            'user' => $user,
            'brand' => $brand,
            'stats' => [
                'postsCount' => $brand?->posts_count ?? 0,
                'subscribersCount' => $brand?->confirmed_subscribers_count ?? 0,
                'draftsCount' => $brand?->drafts_count ?? 0,
            ],
        ]);
    }
}
