<?php

namespace App\Http\Controllers;

use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Brands/Create');
    }

    public function edit(): Response|RedirectResponse
    {
        $brand = auth()->user()->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        return Inertia::render('Settings/Brand', [
            'brand' => (new BrandResource($brand))->resolve(),
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $account = $request->user()->currentAccount();

        if (! $account) {
            return redirect()->route('dashboard')->with('error', 'No account found.');
        }

        $validated = $request->validated();

        $account->brands()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'tagline' => $validated['tagline'] ?? null,
            'description' => $validated['description'] ?? null,
            'industry' => $validated['industry'] ?? null,
            'primary_color' => $validated['primary_color'] ?? '#6366f1',
        ]);

        return redirect()->route('dashboard')->with('success', 'Brand created successfully!');
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validated();

        $brand->update($validated);

        return back()->with('success', 'Brand updated successfully!');
    }

    public function switch(Brand $brand): RedirectResponse
    {
        $user = auth()->user();
        $account = $user->currentAccount();

        if (! $account || ! $account->brands()->where('brands.id', $brand->id)->exists()) {
            return back()->with('error', 'Brand not found.');
        }

        $user->switchBrand($brand);

        return back()->with('success', "Switched to {$brand->name}");
    }
}
