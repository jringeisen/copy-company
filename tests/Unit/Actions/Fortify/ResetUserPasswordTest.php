<?php

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('it resets user password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    $action = new ResetUserPassword;

    $action->reset($user, [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $user->refresh();

    expect(Hash::check('NewPassword123!', $user->password))->toBeTrue()
        ->and(Hash::check('OldPassword123!', $user->password))->toBeFalse();
});

test('it validates password confirmation', function () {
    $user = User::factory()->create();

    $action = new ResetUserPassword;

    $action->reset($user, [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'DifferentPassword!',
    ]);
})->throws(ValidationException::class);

test('it validates minimum password length', function () {
    $user = User::factory()->create();

    $action = new ResetUserPassword;

    $action->reset($user, [
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);
})->throws(ValidationException::class);

test('it requires password field', function () {
    $user = User::factory()->create();

    $action = new ResetUserPassword;

    $action->reset($user, [
        'password' => '',
        'password_confirmation' => '',
    ]);
})->throws(ValidationException::class);

test('it hashes the new password', function () {
    $user = User::factory()->create();

    $action = new ResetUserPassword;

    $action->reset($user, [
        'password' => 'NewSecurePassword123!',
        'password_confirmation' => 'NewSecurePassword123!',
    ]);

    $user->refresh();

    expect($user->password)->not->toBe('NewSecurePassword123!');
});
