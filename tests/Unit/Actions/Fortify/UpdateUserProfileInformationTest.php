<?php

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('it updates user name', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'test@example.com',
    ]);

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Updated Name',
        'email' => 'test@example.com',
    ]);

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
});

test('it updates user email', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'old@example.com',
    ]);

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Test User',
        'email' => 'new@example.com',
    ]);

    $user->refresh();

    expect($user->email)->toBe('new@example.com');
});

test('it validates required name', function () {
    $user = User::factory()->create();

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => '',
        'email' => 'test@example.com',
    ]);
})->throws(ValidationException::class);

test('it validates required email', function () {
    $user = User::factory()->create();

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Test User',
        'email' => '',
    ]);
})->throws(ValidationException::class);

test('it validates email format', function () {
    $user = User::factory()->create();

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Test User',
        'email' => 'not-an-email',
    ]);
})->throws(ValidationException::class);

test('it validates email uniqueness', function () {
    User::factory()->create(['email' => 'existing@example.com']);
    $user = User::factory()->create(['email' => 'current@example.com']);

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Test User',
        'email' => 'existing@example.com',
    ]);
})->throws(ValidationException::class);

test('it allows keeping same email', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'same@example.com',
    ]);

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Updated Name',
        'email' => 'same@example.com',
    ]);

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('same@example.com');
});

test('it validates name max length', function () {
    $user = User::factory()->create();

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => str_repeat('a', 256),
        'email' => 'test@example.com',
    ]);
})->throws(ValidationException::class);

test('it validates email max length', function () {
    $user = User::factory()->create();

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Test User',
        'email' => str_repeat('a', 250).'@example.com',
    ]);
})->throws(ValidationException::class);

test('it updates both name and email simultaneously', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');
});

test('it resets email verification and sends notification for MustVerifyEmail users when email changes', function () {
    // Create a user class that implements MustVerifyEmail
    $user = new class extends User implements \Illuminate\Contracts\Auth\MustVerifyEmail
    {
        use \Illuminate\Auth\MustVerifyEmail;

        protected $table = 'users';

        protected $fillable = ['name', 'email', 'password', 'email_verified_at'];
    };

    // Create the user in database
    $user->forceFill([
        'name' => 'Test User',
        'email' => 'old@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ])->save();

    \Illuminate\Support\Facades\Notification::fake();

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Test User',
        'email' => 'new@example.com',
    ]);

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();

    \Illuminate\Support\Facades\Notification::assertSentTo(
        $user,
        \Illuminate\Auth\Notifications\VerifyEmail::class
    );
});

test('it does not reset verification when email stays the same for MustVerifyEmail users', function () {
    $user = new class extends User implements \Illuminate\Contracts\Auth\MustVerifyEmail
    {
        use \Illuminate\Auth\MustVerifyEmail;

        protected $table = 'users';

        protected $fillable = ['name', 'email', 'password', 'email_verified_at'];
    };

    $verifiedAt = now();
    $user->forceFill([
        'name' => 'Test User',
        'email' => 'same@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => $verifiedAt,
    ])->save();

    \Illuminate\Support\Facades\Notification::fake();

    $action = new UpdateUserProfileInformation;

    $action->update($user, [
        'name' => 'Updated Name',
        'email' => 'same@example.com',
    ]);

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->email_verified_at)->not->toBeNull();

    \Illuminate\Support\Facades\Notification::assertNotSentTo(
        $user,
        \Illuminate\Auth\Notifications\VerifyEmail::class
    );
});
