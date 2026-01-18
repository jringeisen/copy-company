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
        if ($user->currentBrand() === null || ! $user->can('posts.create')) {
            return false;
        }

        // Check subscription post limits
        $account = $user->currentAccount();
        if ($account && ! $account->subscriptionLimits()->canCreatePost()) {
            return false;
        }

        return true;
    }

    public function update(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id && $user->can('posts.update');
    }

    public function delete(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id && $user->can('posts.delete');
    }

    public function restore(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id && $user->can('posts.update');
    }

    public function forceDelete(User $user, Post $post): bool
    {
        $brand = $user->currentBrand();

        return $brand && $post->brand_id === $brand->id && $user->can('posts.delete');
    }
}
