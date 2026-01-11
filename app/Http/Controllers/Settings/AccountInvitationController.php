<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Mail\AccountInvitationMail;
use App\Models\AccountInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AccountInvitationController extends Controller
{
    /**
     * Send a new invitation.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $account = $user->currentAccount();

        if (! $account || ! $account->isAdmin($user)) {
            abort(403, 'You do not have permission to invite team members.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:admin,member,viewer'],
        ]);

        // Check if user is already in account
        if ($account->users()->where('email', $validated['email'])->exists()) {
            return back()->with('error', 'This user is already a member of this account.');
        }

        // Check if invitation already exists and hasn't expired
        $existingInvitation = $account->invitations()
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            return back()->with('error', 'An invitation has already been sent to this email.');
        }

        // Create or update invitation
        $invitation = AccountInvitation::updateOrCreate(
            [
                'account_id' => $account->id,
                'email' => $validated['email'],
            ],
            [
                'invited_by' => $user->id,
                'role' => $validated['role'],
                'token' => AccountInvitation::generateToken(),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ]
        );

        // Send invitation email
        Mail::to($validated['email'])->send(new AccountInvitationMail($invitation));

        return back()->with('success', 'Invitation sent successfully.');
    }

    /**
     * Accept an invitation.
     */
    public function accept(string $token): RedirectResponse
    {
        $invitation = AccountInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation) {
            return redirect()->route('login')
                ->with('error', 'This invitation link is invalid.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')
                ->with('error', 'This invitation has expired. Please ask for a new invitation.');
        }

        // Check if a user with this email exists
        $user = User::where('email', $invitation->email)->first();

        /** @var \App\Models\Account $account */
        $account = $invitation->account;

        if ($user) {
            // User exists - add them to the account
            if ($account->hasMember($user)) {
                return redirect()->route('dashboard')
                    ->with('info', 'You are already a member of this account.');
            }

            $account->users()->attach($user->id, ['role' => $invitation->role]);
            $invitation->markAsAccepted();

            // Assign Spatie role (must set team ID first)
            setPermissionsTeamId($account->id);
            $user->assignRole($invitation->role);

            // Log them in if not already
            if (! auth()->check()) {
                auth()->login($user);
            }

            // Switch to the new account
            $user->switchAccount($account);

            return redirect()->route('dashboard')
                ->with('success', 'You have joined '.$account->name.'!');
        }

        // User doesn't exist - redirect to registration with token in session
        session(['invitation_token' => $token]);

        return redirect()->route('register')
            ->with('info', 'Create an account to join '.$account->name.'.');
    }

    /**
     * Cancel a pending invitation.
     */
    public function destroy(AccountInvitation $invitation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $account = $user->currentAccount();

        if (! $account || $invitation->account_id !== $account->id) {
            abort(404);
        }

        if (! $account->isAdmin($user)) {
            abort(403, 'You do not have permission to cancel invitations.');
        }

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled.');
    }

    /**
     * Resend an invitation.
     */
    public function resend(AccountInvitation $invitation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $account = $user->currentAccount();

        if (! $account || $invitation->account_id !== $account->id) {
            abort(404);
        }

        if (! $account->isAdmin($user)) {
            abort(403, 'You do not have permission to resend invitations.');
        }

        // Refresh the token and expiration
        $invitation->update([
            'token' => AccountInvitation::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        // Resend the email
        Mail::to($invitation->email)->send(new AccountInvitationMail($invitation));

        return back()->with('success', 'Invitation resent successfully.');
    }
}
