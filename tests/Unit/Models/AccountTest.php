<?php

use App\Models\Account;
use App\Models\AccountInvitation;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('account has many users through pivot', function () {
    $account = Account::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $account->users()->attach($user1->id, ['role' => 'admin']);
    $account->users()->attach($user2->id, ['role' => 'member']);

    expect($account->users)->toHaveCount(2);
    expect($account->users->pluck('id')->toArray())->toContain($user1->id, $user2->id);
});

test('account has many brands', function () {
    $account = Account::factory()->create();
    $brand1 = Brand::factory()->forAccount($account)->create();
    $brand2 = Brand::factory()->forAccount($account)->create();

    expect($account->brands)->toHaveCount(2);
    expect($account->brands->pluck('id')->toArray())->toContain($brand1->id, $brand2->id);
});

test('account has many invitations', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();

    $invitation1 = AccountInvitation::factory()->forAccount($account)->create(['invited_by' => $user->id]);
    $invitation2 = AccountInvitation::factory()->forAccount($account)->create(['invited_by' => $user->id]);

    expect($account->invitations)->toHaveCount(2);
});

test('admins scope returns only admin users', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $viewer = User::factory()->create();

    $account->users()->attach($admin->id, ['role' => 'admin']);
    $account->users()->attach($member->id, ['role' => 'member']);
    $account->users()->attach($viewer->id, ['role' => 'viewer']);

    $admins = $account->admins;

    expect($admins)->toHaveCount(1);
    expect($admins->first()->id)->toBe($admin->id);
});

test('isAdmin returns true for admin users', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create();

    $account->users()->attach($admin->id, ['role' => 'admin']);

    expect($account->isAdmin($admin))->toBeTrue();
});

test('isAdmin returns false for member users', function () {
    $account = Account::factory()->create();
    $member = User::factory()->create();

    $account->users()->attach($member->id, ['role' => 'member']);

    expect($account->isAdmin($member))->toBeFalse();
});

test('isAdmin returns false for viewer users', function () {
    $account = Account::factory()->create();
    $viewer = User::factory()->create();

    $account->users()->attach($viewer->id, ['role' => 'viewer']);

    expect($account->isAdmin($viewer))->toBeFalse();
});

test('isAdmin returns false for users not in account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();

    expect($account->isAdmin($user))->toBeFalse();
});

test('hasMember returns true for users in account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();

    $account->users()->attach($user->id, ['role' => 'viewer']);

    expect($account->hasMember($user))->toBeTrue();
});

test('hasMember returns false for users not in account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();

    expect($account->hasMember($user))->toBeFalse();
});

test('getUserRole returns correct role for user', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $viewer = User::factory()->create();

    $account->users()->attach($admin->id, ['role' => 'admin']);
    $account->users()->attach($member->id, ['role' => 'member']);
    $account->users()->attach($viewer->id, ['role' => 'viewer']);

    expect($account->getUserRole($admin))->toBe('admin');
    expect($account->getUserRole($member))->toBe('member');
    expect($account->getUserRole($viewer))->toBe('viewer');
});

test('getUserRole returns null for users not in account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();

    expect($account->getUserRole($user))->toBeNull();
});

test('pivot table stores role correctly', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();

    $account->users()->attach($user->id, ['role' => 'member']);

    $member = $account->users()->where('users.id', $user->id)->first();
    expect($member->pivot->role)->toBe('member');
});

test('pivot table stores timestamps', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();

    $account->users()->attach($user->id, ['role' => 'admin']);

    $member = $account->users()->where('users.id', $user->id)->first();
    expect($member->pivot->created_at)->not->toBeNull();
    expect($member->pivot->updated_at)->not->toBeNull();
});
