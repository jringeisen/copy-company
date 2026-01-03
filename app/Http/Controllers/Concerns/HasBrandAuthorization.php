<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;

trait HasBrandAuthorization
{
    /**
     * Get the current brand for the authenticated user.
     */
    protected function currentBrand(): ?Brand
    {
        return auth()->user()->currentBrand();
    }

    /**
     * Get the current brand or redirect to brand creation if none exists.
     */
    protected function requireBrand(): Brand|RedirectResponse
    {
        $brand = $this->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        return $brand;
    }

    /**
     * Check if a redirect response was returned from requireBrand.
     */
    protected function isRedirect(mixed $result): bool
    {
        return $result instanceof RedirectResponse;
    }
}
