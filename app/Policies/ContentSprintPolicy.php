<?php

namespace App\Policies;

use App\Models\ContentSprint;
use App\Models\User;

class ContentSprintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function view(User $user, ContentSprint $contentSprint): bool
    {
        $brand = $user->currentBrand();

        return $brand && $contentSprint->brand_id === $brand->id;
    }

    public function create(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function update(User $user, ContentSprint $contentSprint): bool
    {
        $brand = $user->currentBrand();

        return $brand && $contentSprint->brand_id === $brand->id;
    }

    public function delete(User $user, ContentSprint $contentSprint): bool
    {
        $brand = $user->currentBrand();

        return $brand && $contentSprint->brand_id === $brand->id;
    }

    public function restore(User $user, ContentSprint $contentSprint): bool
    {
        $brand = $user->currentBrand();

        return $brand && $contentSprint->brand_id === $brand->id;
    }

    public function forceDelete(User $user, ContentSprint $contentSprint): bool
    {
        $brand = $user->currentBrand();

        return $brand && $contentSprint->brand_id === $brand->id;
    }
}
