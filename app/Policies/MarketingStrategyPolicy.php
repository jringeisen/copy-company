<?php

namespace App\Policies;

use App\Models\MarketingStrategy;
use App\Models\User;

class MarketingStrategyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function view(User $user, MarketingStrategy $marketingStrategy): bool
    {
        $brand = $user->currentBrand();

        return $brand && $marketingStrategy->brand_id === $brand->id;
    }

    public function update(User $user, MarketingStrategy $marketingStrategy): bool
    {
        $brand = $user->currentBrand();

        return $brand && $marketingStrategy->brand_id === $brand->id;
    }
}
