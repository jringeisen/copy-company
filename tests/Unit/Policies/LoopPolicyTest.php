<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\Loop;
use App\Models\User;
use App\Policies\LoopPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new LoopPolicy;

    Permission::findOrCreate('social.manage', 'web');
    Permission::findOrCreate('social.publish', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['social.manage', 'social.publish']);
});

test('viewAny returns true when user has a current brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('viewAny returns false when user has no current brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    session(['current_account_id' => $account->id]);

    expect($this->policy->viewAny($user))->toBeFalse();
});

test('user can view their own account brand loops', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);

    expect($this->policy->view($user, $loop))->toBeTrue();
});

test('user cannot view loops from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $loop = Loop::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);

    expect($this->policy->view($user, $loop))->toBeFalse();
});

test('user can create loops when has brand and social.manage permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->create($user))->toBeTrue();
});

test('user cannot create loops without social.manage permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    // Don't assign role, so no permissions

    expect($this->policy->create($user))->toBeFalse();
});

test('user can update their own account brand loops with social.manage permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->update($user, $loop))->toBeTrue();
});

test('user cannot update loops from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $loop = Loop::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->update($user, $loop))->toBeFalse();
});

test('user cannot update loops without social.manage permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    // Don't assign role, so no permissions

    expect($this->policy->update($user, $loop))->toBeFalse();
});

test('user can delete their own account brand loops with social.manage permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->delete($user, $loop))->toBeTrue();
});

test('user cannot delete loops from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $loop = Loop::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->delete($user, $loop))->toBeFalse();
});

test('user cannot delete loops without social.manage permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    // Don't assign role, so no permissions

    expect($this->policy->delete($user, $loop))->toBeFalse();
});
