<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Policies\SocialPostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new SocialPostPolicy;

    // Create permissions needed for social post operations
    Permission::findOrCreate('social.manage', 'web');
    Permission::findOrCreate('social.publish', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['social.manage', 'social.publish']);
});

test('user can view their own account brand social posts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);

    expect($this->policy->view($user, $socialPost))->toBeTrue();
});

test('user cannot view social posts from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $socialPost = SocialPost::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);

    expect($this->policy->view($user, $socialPost))->toBeFalse();
});

test('user can update their own account brand social posts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->update($user, $socialPost))->toBeTrue();
});

test('user cannot update social posts from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $socialPost = SocialPost::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->update($user, $socialPost))->toBeFalse();
});

test('user can delete their own account brand social posts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->delete($user, $socialPost))->toBeTrue();
});

test('user cannot delete social posts from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $socialPost = SocialPost::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->delete($user, $socialPost))->toBeFalse();
});
