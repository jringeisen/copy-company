<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\Subscriber;
use App\Models\User;
use App\Policies\SubscriberPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new SubscriberPolicy;

    // Create permissions needed for subscriber operations
    Permission::findOrCreate('subscribers.view', 'web');
    Permission::findOrCreate('subscribers.export', 'web');
    Permission::findOrCreate('subscribers.delete', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['subscribers.view', 'subscribers.export', 'subscribers.delete']);
});

test('user can view their own account brand subscribers', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->view($user, $subscriber))->toBeTrue();
});

test('user cannot view subscribers from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->view($user, $subscriber))->toBeFalse();
});

test('user can delete their own account brand subscribers', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->delete($user, $subscriber))->toBeTrue();
});

test('user cannot delete subscribers from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->delete($user, $subscriber))->toBeFalse();
});

test('user can view any subscribers if they have a brand and permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('user cannot view any subscribers if they have no brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->viewAny($user))->toBeFalse();
});

test('user can create subscribers if they have a brand and permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->create($user))->toBeTrue();
});

test('user cannot create subscribers if they have no brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->create($user))->toBeFalse();
});

test('user can update their own account brand subscribers', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->update($user, $subscriber))->toBeTrue();
});

test('user cannot update subscribers from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->update($user, $subscriber))->toBeFalse();
});

test('user can export subscribers if they have a brand and permission', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->export($user))->toBeTrue();
});

test('user cannot export subscribers if they have no brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->export($user))->toBeFalse();
});

test('user can restore their own account brand subscribers', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->restore($user, $subscriber))->toBeTrue();
});

test('user cannot restore subscribers from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->restore($user, $subscriber))->toBeFalse();
});

test('user can force delete their own account brand subscribers', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->forceDelete($user, $subscriber))->toBeTrue();
});

test('user cannot force delete subscribers from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->forceDelete($user, $subscriber))->toBeFalse();
});
