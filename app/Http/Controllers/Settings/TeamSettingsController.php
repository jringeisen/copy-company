<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamSettingsController extends Controller
{
    /**
     * Show the team settings page.
     */
    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();
        $account = $user->currentAccount();

        if (! $account) {
            return redirect()->route('dashboard');
        }

        $members = $account->users()
            ->get()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
                'joined_at' => $member->pivot->created_at->toIso8601String(),
                'is_current_user' => $member->id === $user->id,
            ]);

        $pendingInvitations = $account->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->get()
            ->map(fn ($invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'invited_by' => $invitation->inviter->name,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ]);

        return Inertia::render('Settings/Team', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
            ],
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'isAdmin' => $account->isAdmin($user),
            'roles' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'member', 'label' => 'Member'],
                ['value' => 'viewer', 'label' => 'Viewer'],
            ],
        ]);
    }

    /**
     * Update a team member's role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $currentUser = auth()->user();
        $account = $currentUser->currentAccount();

        if (! $account || ! $account->isAdmin($currentUser)) {
            abort(403, 'You do not have permission to manage team members.');
        }

        // Cannot change your own role
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        // Verify the user is a member of this account
        if (! $account->hasMember($user)) {
            abort(404, 'User not found in this account.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,member,viewer'],
        ]);

        // Update the role in the pivot table
        $account->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        // Sync Spatie role
        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'Team member role updated successfully.');
    }

    /**
     * Remove a team member.
     */
    public function removeMember(User $user): RedirectResponse
    {
        $currentUser = auth()->user();
        $account = $currentUser->currentAccount();

        if (! $account || ! $account->isAdmin($currentUser)) {
            abort(403, 'You do not have permission to remove team members.');
        }

        // Cannot remove yourself
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'You cannot remove yourself from the account.');
        }

        // Verify the user is a member of this account
        if (! $account->hasMember($user)) {
            abort(404, 'User not found in this account.');
        }

        // Ensure at least one admin remains
        if ($account->admins()->count() === 1 && $account->isAdmin($user)) {
            return back()->with('error', 'Cannot remove the last admin from the account.');
        }

        // Remove the user from the account
        $account->users()->detach($user->id);

        return back()->with('success', 'Team member removed successfully.');
    }
}
