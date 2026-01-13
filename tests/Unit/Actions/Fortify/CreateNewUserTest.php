<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\Account;
use App\Models\AccountInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    Role::findOrCreate('member', 'web');
});

test('it creates a new user with valid input', function () {
    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com');
});

test('it creates an account for new user', function () {
    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    expect($user->accounts)->toHaveCount(1)
        ->and($user->accounts->first()->name)->toBe("Jane Doe's Account");
});

test('it assigns admin role to new user', function () {
    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $account = $user->accounts->first();
    setPermissionsTeamId($account->id);

    expect($user->hasRole('admin'))->toBeTrue();
});

test('it validates required fields', function () {
    $action = new CreateNewUser;

    $action->create([
        'name' => '',
        'email' => '',
        'password' => '',
    ]);
})->throws(ValidationException::class);

test('it validates email uniqueness', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $action = new CreateNewUser;

    $action->create([
        'name' => 'New User',
        'email' => 'existing@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);
})->throws(ValidationException::class);

test('it validates email format', function () {
    $action = new CreateNewUser;

    $action->create([
        'name' => 'Invalid Email',
        'email' => 'not-an-email',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);
})->throws(ValidationException::class);

test('it validates password confirmation', function () {
    $action = new CreateNewUser;

    $action->create([
        'name' => 'Password Mismatch',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword123!',
    ]);
})->throws(ValidationException::class);

test('it accepts pending invitation', function () {
    $inviter = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($inviter->id, ['role' => 'admin']);

    $invitation = AccountInvitation::create([
        'account_id' => $account->id,
        'invited_by' => $inviter->id,
        'email' => 'invited@example.com',
        'role' => 'member',
        'token' => 'test-token',
        'expires_at' => now()->addDays(7),
    ]);

    session(['invitation_token' => 'test-token']);

    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Invited User',
        'email' => 'invited@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    expect($user->accounts)->toHaveCount(1)
        ->and($user->accounts->first()->id)->toBe($account->id);

    $invitation->refresh();
    expect($invitation->accepted_at)->not->toBeNull();
});

test('it ignores expired invitation', function () {
    $inviter = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($inviter->id, ['role' => 'admin']);

    AccountInvitation::create([
        'account_id' => $account->id,
        'invited_by' => $inviter->id,
        'email' => 'expired@example.com',
        'role' => 'member',
        'token' => 'expired-token',
        'expires_at' => now()->subDays(1),
    ]);

    session(['invitation_token' => 'expired-token']);

    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Expired Invite User',
        'email' => 'expired@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    // Should create new account since invitation is expired
    expect($user->accounts->first()->name)->toBe("Expired Invite User's Account");
});

test('it ignores invitation with wrong email', function () {
    $inviter = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($inviter->id, ['role' => 'admin']);

    AccountInvitation::create([
        'account_id' => $account->id,
        'invited_by' => $inviter->id,
        'email' => 'different@example.com',
        'role' => 'member',
        'token' => 'wrong-email-token',
        'expires_at' => now()->addDays(7),
    ]);

    session(['invitation_token' => 'wrong-email-token']);

    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Wrong Email User',
        'email' => 'other@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    // Should create new account since emails don't match
    expect($user->accounts->first()->name)->toBe("Wrong Email User's Account");
});

test('it generates unique slug for account', function () {
    // Create first user
    $action = new CreateNewUser;
    $user1 = $action->create([
        'name' => 'Test User',
        'email' => 'test1@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    // Create second user with same name
    $user2 = $action->create([
        'name' => 'Test User',
        'email' => 'test2@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $account1 = $user1->accounts->first();
    $account2 = $user2->accounts->first();

    expect($account1->slug)->toBe('test-user')
        ->and($account2->slug)->toBe('test-user-1');
});

test('it ignores already accepted invitation', function () {
    $inviter = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($inviter->id, ['role' => 'admin']);

    AccountInvitation::create([
        'account_id' => $account->id,
        'invited_by' => $inviter->id,
        'email' => 'accepted@example.com',
        'role' => 'member',
        'token' => 'accepted-token',
        'expires_at' => now()->addDays(7),
        'accepted_at' => now(),
    ]);

    session(['invitation_token' => 'accepted-token']);

    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Already Accepted User',
        'email' => 'accepted@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    // Should create new account since invitation is already accepted
    expect($user->accounts->first()->name)->toBe("Already Accepted User's Account");
});

test('it hashes the password', function () {
    $action = new CreateNewUser;

    $user = $action->create([
        'name' => 'Hash Test',
        'email' => 'hash@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    expect($user->password)->not->toBe('Password123!')
        ->and(password_verify('Password123!', $user->password))->toBeTrue();
});
