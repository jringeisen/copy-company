<?php

namespace App\Actions\Fortify;

use App\Models\Account;
use App\Models\AccountInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            // Check for pending invitation
            $invitationToken = session('invitation_token');

            if ($invitationToken) {
                /** @var AccountInvitation|null $invitation */
                $invitation = AccountInvitation::where('token', $invitationToken)
                    ->whereNull('accepted_at')
                    ->first();

                if ($invitation && $invitation->isValid() && $invitation->email === $user->email) {
                    // Accept the invitation
                    /** @var Account $invitationAccount */
                    $invitationAccount = $invitation->account;
                    $invitationAccount->users()->attach($user->id, ['role' => $invitation->role]);
                    $invitation->markAsAccepted();

                    // Set team context and assign role if role exists
                    setPermissionsTeamId($invitationAccount->id);
                    if (\Spatie\Permission\Models\Role::where('name', $invitation->role)->exists()) {
                        $user->assignRole($invitation->role);
                    }

                    session()->forget('invitation_token');

                    return $user;
                }
            }

            // No valid invitation - create a new account for the user
            $account = Account::create([
                'name' => $user->name."'s Account",
                'slug' => $this->generateUniqueSlug($user->name),
                'trial_ends_at' => now()->addDays(14),
            ]);

            // Create Stripe customer for the account (if Stripe is configured)
            if (config('cashier.secret')) {
                $account->createAsStripeCustomer([
                    'name' => $account->name,
                    'email' => $user->email,
                ]);
            }

            // Attach user as admin
            $account->users()->attach($user->id, ['role' => 'admin']);

            // Set team context and assign role if role exists
            setPermissionsTeamId($account->id);
            if (\Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
                $user->assignRole('admin');
            }

            return $user;
        });
    }

    /**
     * Generate a unique slug for the account.
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Account::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
