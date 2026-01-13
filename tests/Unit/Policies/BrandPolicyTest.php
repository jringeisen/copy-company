<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\User;
use App\Policies\BrandPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new BrandPolicy;

    // Create permissions
    Permission::findOrCreate('brands.create', 'web');
    Permission::findOrCreate('brands.update', 'web');
    Permission::findOrCreate('brands.delete', 'web');

    // Create roles and assign permissions
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['brands.create', 'brands.update', 'brands.delete']);

    Role::findOrCreate('member', 'web');
    Role::findOrCreate('viewer', 'web');
});

test('user can view their own account brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    // Set current account in session
    session(['current_account_id' => $account->id]);

    expect($this->policy->view($user, $brand))->toBeTrue();
});

test('user cannot view other accounts brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);

    $otherAccount = Account::factory()->create();
    $brand = Brand::factory()->forAccount($otherAccount)->create();

    // Set user's current account in session
    session(['current_account_id' => $userAccount->id]);

    expect($this->policy->view($user, $brand))->toBeFalse();
});

test('admin can update their own account brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    // Set account context for role/permission assignment
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    session(['current_account_id' => $account->id]);

    expect($this->policy->update($user, $brand))->toBeTrue();
});

test('user cannot update other accounts brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);

    // Set account context for role/permission assignment
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    $otherAccount = Account::factory()->create();
    $brand = Brand::factory()->forAccount($otherAccount)->create();

    session(['current_account_id' => $userAccount->id]);

    expect($this->policy->update($user, $brand))->toBeFalse();
});

test('admin can delete their own account brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    // Set account context for role/permission assignment
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    session(['current_account_id' => $account->id]);

    expect($this->policy->delete($user, $brand))->toBeTrue();
});

test('user cannot delete other accounts brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);

    // Set account context for role/permission assignment
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    $otherAccount = Account::factory()->create();
    $brand = Brand::factory()->forAccount($otherAccount)->create();

    session(['current_account_id' => $userAccount->id]);

    expect($this->policy->delete($user, $brand))->toBeFalse();
});

test('any user can view any brands', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('admin with permission can create brands', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    // Set account context for role/permission assignment
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    session(['current_account_id' => $account->id]);

    expect($this->policy->create($user))->toBeTrue();
});

test('user without permission cannot create brands', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'member']);

    // Set account context for role/permission assignment
    setPermissionsTeamId($account->id);
    $user->assignRole('member');

    session(['current_account_id' => $account->id]);

    expect($this->policy->create($user))->toBeFalse();
});

test('user without account cannot create brands', function () {
    $user = User::factory()->create();

    // No account set in session
    expect($this->policy->create($user))->toBeFalse();
});
