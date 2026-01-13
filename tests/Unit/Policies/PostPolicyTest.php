<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new PostPolicy;

    // Create permissions needed for post operations
    Permission::findOrCreate('posts.create', 'web');
    Permission::findOrCreate('posts.update', 'web');
    Permission::findOrCreate('posts.delete', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['posts.create', 'posts.update', 'posts.delete']);
});

test('user can view any posts if they have a brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('user cannot view any posts if they have no brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    session(['current_account_id' => $account->id]);

    expect($this->policy->viewAny($user))->toBeFalse();
});

test('user can view their own account brand posts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create();

    session(['current_account_id' => $account->id]);

    expect($this->policy->view($user, $post))->toBeTrue();
});

test('user cannot view posts from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);

    expect($this->policy->view($user, $post))->toBeFalse();
});

test('user can create posts if they have a brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->create($user))->toBeTrue();
});

test('user cannot create posts if they have no brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->create($user))->toBeFalse();
});

test('user can update their own account brand posts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->update($user, $post))->toBeTrue();
});

test('user cannot update posts from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->update($user, $post))->toBeFalse();
});

test('user can delete their own account brand posts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->delete($user, $post))->toBeTrue();
});

test('user cannot delete posts from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->delete($user, $post))->toBeFalse();
});

test('user can restore their own account brand posts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->restore($user, $post))->toBeTrue();
});

test('user cannot restore posts from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->restore($user, $post))->toBeFalse();
});

test('user can force delete their own account brand posts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    expect($this->policy->forceDelete($user, $post))->toBeTrue();
});

test('user cannot force delete posts from other account brands', function () {
    $user = User::factory()->create();
    $userAccount = Account::factory()->create();
    $userAccount->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($userAccount)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    session(['current_account_id' => $userAccount->id]);
    setPermissionsTeamId($userAccount->id);
    $user->assignRole('admin');

    expect($this->policy->forceDelete($user, $post))->toBeFalse();
});
