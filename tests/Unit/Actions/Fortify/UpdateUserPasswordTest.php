<?php

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('it updates user password with valid current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
    ]);

    // Login the user so current_password validation works
    $this->actingAs($user);

    $action = new UpdateUserPassword;

    $action->update($user, [
        'current_password' => 'CurrentPassword123!',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $user->refresh();

    expect(Hash::check('NewPassword123!', $user->password))->toBeTrue();
});

test('it fails with incorrect current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
    ]);

    $this->actingAs($user);

    $action = new UpdateUserPassword;

    $action->update($user, [
        'current_password' => 'WrongPassword!',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);
})->throws(ValidationException::class);

test('it validates password confirmation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
    ]);

    $this->actingAs($user);

    $action = new UpdateUserPassword;

    $action->update($user, [
        'current_password' => 'CurrentPassword123!',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'DifferentPassword!',
    ]);
})->throws(ValidationException::class);

test('it requires current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
    ]);

    $this->actingAs($user);

    $action = new UpdateUserPassword;

    $action->update($user, [
        'current_password' => '',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);
})->throws(ValidationException::class);

test('it validates password minimum length', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
    ]);

    $this->actingAs($user);

    $action = new UpdateUserPassword;

    $action->update($user, [
        'current_password' => 'CurrentPassword123!',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);
})->throws(ValidationException::class);

test('it hashes the new password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CurrentPassword123!'),
    ]);

    $this->actingAs($user);

    $action = new UpdateUserPassword;

    $action->update($user, [
        'current_password' => 'CurrentPassword123!',
        'password' => 'SecureNewPassword123!',
        'password_confirmation' => 'SecureNewPassword123!',
    ]);

    $user->refresh();

    expect($user->password)->not->toBe('SecureNewPassword123!');
});
