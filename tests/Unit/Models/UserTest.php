<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create();
    $this->account->users()->attach($this->user->id, ['role' => 'admin']);
    $this->brand = Brand::factory()->forAccount($this->account)->create();

    session(['current_account_id' => $this->account->id]);
});

test('user has many accounts through pivot', function () {
    expect($this->user->accounts)->toHaveCount(1)
        ->and($this->user->accounts->first()->id)->toBe($this->account->id);
});

test('user can have multiple accounts', function () {
    $secondAccount = Account::factory()->create();
    $secondAccount->users()->attach($this->user->id, ['role' => 'member']);

    $this->user->refresh();

    expect($this->user->accounts)->toHaveCount(2);
});

test('currentAccount returns account from session', function () {
    expect($this->user->currentAccount())->not->toBeNull()
        ->and($this->user->currentAccount()->id)->toBe($this->account->id);
});

test('currentAccount returns first account when session empty', function () {
    session()->forget('current_account_id');
    $freshUser = User::find($this->user->id);

    expect($freshUser->currentAccount())->not->toBeNull()
        ->and($freshUser->currentAccount()->id)->toBe($this->account->id);
});

test('currentAccount returns null when user has no accounts', function () {
    $userWithoutAccounts = User::factory()->create();

    expect($userWithoutAccounts->currentAccount())->toBeNull();
});

test('currentAccount caches result for subsequent calls', function () {
    // First call should cache
    $firstCall = $this->user->currentAccount();

    // Update session to different value (but cache should prevent re-query)
    session(['current_account_id' => 999999]);

    // Second call should return cached value
    $secondCall = $this->user->currentAccount();

    expect($firstCall->id)->toBe($secondCall->id);
});

test('currentAccount falls back to first account when session account not found', function () {
    session(['current_account_id' => 999999]); // Non-existent account
    $freshUser = User::find($this->user->id);

    expect($freshUser->currentAccount())->not->toBeNull()
        ->and($freshUser->currentAccount()->id)->toBe($this->account->id);
});

test('switchAccount updates session and clears cache', function () {
    $secondAccount = Account::factory()->create();
    $secondAccount->users()->attach($this->user->id, ['role' => 'member']);

    // First, get current account to cache it
    $this->user->currentAccount();

    // Switch to new account
    $this->user->switchAccount($secondAccount);

    expect(session('current_account_id'))->toBe($secondAccount->id)
        ->and(session('current_brand_id'))->toBeNull();
});

test('switchAccount does not switch to account user does not belong to', function () {
    $otherAccount = Account::factory()->create();
    $originalAccountId = session('current_account_id');

    $this->user->switchAccount($otherAccount);

    expect(session('current_account_id'))->toBe($originalAccountId);
});

test('brands returns brands from current account', function () {
    Brand::factory()->forAccount($this->account)->count(2)->create();

    expect($this->user->brands()->count())->toBe(3); // Original + 2 new
});

test('brands returns empty collection when no current account', function () {
    $userWithoutAccounts = User::factory()->create();

    expect($userWithoutAccounts->brands()->count())->toBe(0);
});

test('currentBrand returns brand from session', function () {
    session(['current_brand_id' => $this->brand->id]);
    $freshUser = User::find($this->user->id);

    expect($freshUser->currentBrand())->not->toBeNull()
        ->and($freshUser->currentBrand()->id)->toBe($this->brand->id);
});

test('currentBrand returns first brand when session empty', function () {
    session()->forget('current_brand_id');
    $freshUser = User::find($this->user->id);

    expect($freshUser->currentBrand())->not->toBeNull()
        ->and($freshUser->currentBrand()->id)->toBe($this->brand->id);
});

test('currentBrand returns null when no current account', function () {
    $userWithoutAccounts = User::factory()->create();

    expect($userWithoutAccounts->currentBrand())->toBeNull();
});

test('currentBrand falls back to first brand when session brand not found', function () {
    session(['current_brand_id' => 999999]); // Non-existent brand
    $freshUser = User::find($this->user->id);

    expect($freshUser->currentBrand())->not->toBeNull()
        ->and($freshUser->currentBrand()->id)->toBe($this->brand->id);
});

test('currentBrand caches result', function () {
    // First call
    $firstCall = $this->user->currentBrand();

    // Attempt to change session
    session(['current_brand_id' => 999999]);

    // Should return cached value
    $secondCall = $this->user->currentBrand();

    expect($firstCall->id)->toBe($secondCall->id);
});

test('switchBrand updates session and clears cache', function () {
    $secondBrand = Brand::factory()->forAccount($this->account)->create();

    // Cache current brand
    $this->user->currentBrand();

    $this->user->switchBrand($secondBrand);

    expect(session('current_brand_id'))->toBe($secondBrand->id);
});

test('switchBrand does not switch to brand from different account', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();

    session(['current_brand_id' => $this->brand->id]);

    $this->user->switchBrand($otherBrand);

    expect(session('current_brand_id'))->toBe($this->brand->id);
});

test('isAccountAdmin returns true for admin role', function () {
    expect($this->user->isAccountAdmin())->toBeTrue();
});

test('isAccountAdmin returns false for member role', function () {
    $memberUser = User::factory()->create();
    $this->account->users()->attach($memberUser->id, ['role' => 'member']);

    expect($memberUser->isAccountAdmin())->toBeFalse();
});

test('isAccountAdmin returns false when no current account', function () {
    $userWithoutAccounts = User::factory()->create();

    expect($userWithoutAccounts->isAccountAdmin())->toBeFalse();
});

test('getAccountRole returns correct role', function () {
    expect($this->user->getAccountRole())->toBe('admin');

    $memberUser = User::factory()->create();
    $this->account->users()->attach($memberUser->id, ['role' => 'member']);

    expect($memberUser->getAccountRole())->toBe('member');
});

test('getAccountRole returns null when no current account', function () {
    $userWithoutAccounts = User::factory()->create();

    expect($userWithoutAccounts->getAccountRole())->toBeNull();
});

test('user has many posts', function () {
    Post::factory()->forBrand($this->brand)->count(3)->create(['user_id' => $this->user->id]);

    expect($this->user->posts)->toHaveCount(3);
});

test('user password is cast as hashed', function () {
    $user = User::factory()->create(['password' => 'plaintext']);

    expect($user->password)->not->toBe('plaintext');
});

test('email_verified_at is cast to datetime', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    expect($user->email_verified_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('sensitive attributes are hidden', function () {
    $user = User::factory()->create();
    $array = $user->toArray();

    expect($array)->not->toHaveKey('password')
        ->and($array)->not->toHaveKey('remember_token')
        ->and($array)->not->toHaveKey('two_factor_secret')
        ->and($array)->not->toHaveKey('two_factor_recovery_codes');
});
