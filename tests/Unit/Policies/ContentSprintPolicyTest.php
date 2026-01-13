<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\User;
use App\Policies\ContentSprintPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('sprints.create', 'web');
    Permission::findOrCreate('sprints.manage', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['sprints.create', 'sprints.manage']);

    $this->policy = new ContentSprintPolicy;
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create();
    $this->account->users()->attach($this->user->id, ['role' => 'admin']);
    $this->brand = Brand::factory()->forAccount($this->account)->create();
    $this->sprint = ContentSprint::factory()->forBrand($this->brand)->create();

    session(['current_account_id' => $this->account->id]);
    setPermissionsTeamId($this->account->id);
    $this->user->assignRole('admin');
});

test('user with brand can view any sprints', function () {
    expect($this->policy->viewAny($this->user))->toBeTrue();
});

test('user without brand cannot view any sprints', function () {
    $userWithoutBrand = User::factory()->create();

    expect($this->policy->viewAny($userWithoutBrand))->toBeFalse();
});

test('user can view sprint belonging to their brand', function () {
    expect($this->policy->view($this->user, $this->sprint))->toBeTrue();
});

test('user cannot view sprint from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherSprint = ContentSprint::factory()->forBrand($otherBrand)->create();

    expect($this->policy->view($this->user, $otherSprint))->toBeFalse();
});

test('user with permission can create sprints', function () {
    expect($this->policy->create($this->user))->toBeTrue();
});

test('user without permission cannot create sprints', function () {
    $userWithoutPermission = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($userWithoutPermission->id, ['role' => 'member']);
    Brand::factory()->forAccount($account)->create();
    session(['current_account_id' => $account->id]);

    expect($this->policy->create($userWithoutPermission))->toBeFalse();
});

test('user with permission can update their sprints', function () {
    expect($this->policy->update($this->user, $this->sprint))->toBeTrue();
});

test('user cannot update sprints from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherSprint = ContentSprint::factory()->forBrand($otherBrand)->create();

    expect($this->policy->update($this->user, $otherSprint))->toBeFalse();
});

test('user with permission can delete their sprints', function () {
    expect($this->policy->delete($this->user, $this->sprint))->toBeTrue();
});

test('user cannot delete sprints from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherSprint = ContentSprint::factory()->forBrand($otherBrand)->create();

    expect($this->policy->delete($this->user, $otherSprint))->toBeFalse();
});

test('user with permission can restore their sprints', function () {
    expect($this->policy->restore($this->user, $this->sprint))->toBeTrue();
});

test('user cannot restore sprints from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherSprint = ContentSprint::factory()->forBrand($otherBrand)->create();

    expect($this->policy->restore($this->user, $otherSprint))->toBeFalse();
});

test('user with permission can force delete their sprints', function () {
    expect($this->policy->forceDelete($this->user, $this->sprint))->toBeTrue();
});

test('user cannot force delete sprints from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherSprint = ContentSprint::factory()->forBrand($otherBrand)->create();

    expect($this->policy->forceDelete($this->user, $otherSprint))->toBeFalse();
});
