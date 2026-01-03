<?php

namespace App\Policies;

use App\Models\Subscriber;
use App\Models\User;

class SubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function view(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id;
    }

    public function create(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function update(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id;
    }

    public function delete(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id;
    }

    public function restore(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id;
    }

    public function forceDelete(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id;
    }
}
