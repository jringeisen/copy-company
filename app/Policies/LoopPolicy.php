<?php

namespace App\Policies;

use App\Models\Loop;
use App\Models\User;

class LoopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function view(User $user, Loop $loop): bool
    {
        $brand = $user->currentBrand();

        return $brand && $loop->brand_id === $brand->id;
    }

    public function create(User $user): bool
    {
        return $user->currentBrand() !== null && $user->can('social.manage');
    }

    public function update(User $user, Loop $loop): bool
    {
        $brand = $user->currentBrand();

        return $brand && $loop->brand_id === $brand->id && $user->can('social.manage');
    }

    public function delete(User $user, Loop $loop): bool
    {
        $brand = $user->currentBrand();

        return $brand && $loop->brand_id === $brand->id && $user->can('social.manage');
    }
}
