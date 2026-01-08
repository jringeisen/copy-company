<?php

namespace App\Policies;

use App\Models\Subscriber;
use App\Models\User;

class SubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null && $user->can('subscribers.view');
    }

    public function view(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id && $user->can('subscribers.view');
    }

    public function create(User $user): bool
    {
        // No specific subscribers.create permission exists
        // Members and admins can add subscribers
        return $user->currentBrand() !== null && $user->can('subscribers.view');
    }

    public function update(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        // No specific subscribers.update permission exists
        return $brand && $subscriber->brand_id === $brand->id && $user->can('subscribers.view');
    }

    public function delete(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id && $user->can('subscribers.delete');
    }

    public function export(User $user): bool
    {
        return $user->currentBrand() !== null && $user->can('subscribers.export');
    }

    public function restore(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id && $user->can('subscribers.delete');
    }

    public function forceDelete(User $user, Subscriber $subscriber): bool
    {
        $brand = $user->currentBrand();

        return $brand && $subscriber->brand_id === $brand->id && $user->can('subscribers.delete');
    }
}
