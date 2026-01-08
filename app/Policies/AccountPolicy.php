<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Account $account): bool
    {
        return $account->hasMember($user);
    }

    public function update(User $user, Account $account): bool
    {
        return $account->isAdmin($user);
    }

    public function delete(User $user, Account $account): bool
    {
        return $account->isAdmin($user);
    }

    /**
     * Determine if the user can manage team members.
     */
    public function manageTeam(User $user, Account $account): bool
    {
        return $account->isAdmin($user);
    }

    /**
     * Determine if the user can invite new members.
     */
    public function invite(User $user, Account $account): bool
    {
        return $account->isAdmin($user)
            && $user->can('team.invite');
    }

    /**
     * Determine if the user can remove members.
     */
    public function removeMember(User $user, Account $account): bool
    {
        return $account->isAdmin($user)
            && $user->can('team.remove');
    }
}
