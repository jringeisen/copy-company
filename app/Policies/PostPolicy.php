<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function view(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id;
    }

    public function create(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function update(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id;
    }

    public function delete(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id;
    }

    public function restore(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id;
    }

    public function forceDelete(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id;
    }
}
