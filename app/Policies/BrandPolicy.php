<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Brand $brand): bool
    {
        $account = $user->currentAccount();

        return $account && $brand->account_id === $account->id;
    }

    public function create(User $user): bool
    {
        return $user->currentAccount() !== null
            && $user->can('brands.create');
    }

    public function update(User $user, Brand $brand): bool
    {
        $account = $user->currentAccount();

        return $account
            && $brand->account_id === $account->id
            && $user->can('brands.update');
    }

    public function delete(User $user, Brand $brand): bool
    {
        $account = $user->currentAccount();

        return $account
            && $brand->account_id === $account->id
            && $user->can('brands.delete');
    }
}
