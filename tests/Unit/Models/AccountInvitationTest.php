<?php

use App\Models\Account;
use App\Models\AccountInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invitation belongs to an account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();
    $invitation = AccountInvitation::factory()
        ->forAccount($account)
        ->create(['invited_by' => $user->id]);

    expect($invitation->account->id)->toBe($account->id);
});

test('invitation belongs to an inviter', function () {
    $account = Account::factory()->create();
    $inviter = User::factory()->create();
    $invitation = AccountInvitation::factory()
        ->forAccount($account)
        ->create(['invited_by' => $inviter->id]);

    expect($invitation->inviter->id)->toBe($inviter->id);
});

test('isExpired returns true for past expiration date', function () {
    $invitation = AccountInvitation::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    expect($invitation->isExpired())->toBeTrue();
});

test('isExpired returns false for future expiration date', function () {
    $invitation = AccountInvitation::factory()->create([
        'expires_at' => now()->addDay(),
    ]);

    expect($invitation->isExpired())->toBeFalse();
});

test('isAccepted returns true when accepted_at is set', function () {
    $invitation = AccountInvitation::factory()->create([
        'accepted_at' => now(),
    ]);

    expect($invitation->isAccepted())->toBeTrue();
});

test('isAccepted returns false when accepted_at is null', function () {
    $invitation = AccountInvitation::factory()->create([
        'accepted_at' => null,
    ]);

    expect($invitation->isAccepted())->toBeFalse();
});

test('isValid returns true for non-expired and non-accepted invitations', function () {
    $invitation = AccountInvitation::factory()->create([
        'expires_at' => now()->addDay(),
        'accepted_at' => null,
    ]);

    expect($invitation->isValid())->toBeTrue();
});

test('isValid returns false for expired invitations', function () {
    $invitation = AccountInvitation::factory()->create([
        'expires_at' => now()->subDay(),
        'accepted_at' => null,
    ]);

    expect($invitation->isValid())->toBeFalse();
});

test('isValid returns false for accepted invitations', function () {
    $invitation = AccountInvitation::factory()->create([
        'expires_at' => now()->addDay(),
        'accepted_at' => now(),
    ]);

    expect($invitation->isValid())->toBeFalse();
});

test('generateToken returns a 64 character string', function () {
    $token = AccountInvitation::generateToken();

    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
});

test('generateToken returns unique values', function () {
    $tokens = [];
    for ($i = 0; $i < 10; $i++) {
        $tokens[] = AccountInvitation::generateToken();
    }

    expect(array_unique($tokens))->toHaveCount(10);
});

test('markAsAccepted sets accepted_at timestamp', function () {
    $invitation = AccountInvitation::factory()->create([
        'accepted_at' => null,
    ]);

    expect($invitation->accepted_at)->toBeNull();

    $invitation->markAsAccepted();
    $invitation->refresh();

    expect($invitation->accepted_at)->not->toBeNull();
});

test('invitation stores email correctly', function () {
    $invitation = AccountInvitation::factory()->create([
        'email' => 'test@example.com',
    ]);

    expect($invitation->email)->toBe('test@example.com');
});

test('invitation stores role correctly', function () {
    $invitation = AccountInvitation::factory()->create([
        'role' => 'member',
    ]);

    expect($invitation->role)->toBe('member');
});

test('invitation can store different roles', function () {
    $admin = AccountInvitation::factory()->create(['role' => 'admin']);
    $member = AccountInvitation::factory()->create(['role' => 'member']);
    $viewer = AccountInvitation::factory()->create(['role' => 'viewer']);

    expect($admin->role)->toBe('admin');
    expect($member->role)->toBe('member');
    expect($viewer->role)->toBe('viewer');
});
