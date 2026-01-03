<?php

namespace App\Policies;

use App\Models\SocialPost;
use App\Models\User;

class SocialPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function view(User $user, SocialPost $socialPost): bool
    {
        $brand = $user->currentBrand();

        return $brand && $socialPost->brand_id === $brand->id;
    }

    public function create(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function update(User $user, SocialPost $socialPost): bool
    {
        $brand = $user->currentBrand();

        return $brand && $socialPost->brand_id === $brand->id;
    }

    public function delete(User $user, SocialPost $socialPost): bool
    {
        $brand = $user->currentBrand();

        return $brand && $socialPost->brand_id === $brand->id;
    }

    public function restore(User $user, SocialPost $socialPost): bool
    {
        $brand = $user->currentBrand();

        return $brand && $socialPost->brand_id === $brand->id;
    }

    public function forceDelete(User $user, SocialPost $socialPost): bool
    {
        $brand = $user->currentBrand();

        return $brand && $socialPost->brand_id === $brand->id;
    }
}
