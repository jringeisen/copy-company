<?php

use App\Models\Account;
use App\Models\User;
use App\Policies\AccountPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('team.invite', 'web');
    Permission::findOrCreate('team.remove', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['team.invite', 'team.remove']);

    $this->policy = new AccountPolicy;
    $this->admin = User::factory()->create();
    $this->member = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->account = Account::factory()->create();
    $this->account->users()->attach($this->admin->id, ['role' => 'admin']);
    $this->account->users()->attach($this->member->id, ['role' => 'member']);

    setPermissionsTeamId($this->account->id);
    $this->admin->assignRole('admin');
});

test('any user can view any accounts', function () {
    expect($this->policy->viewAny($this->admin))->toBeTrue()
        ->and($this->policy->viewAny($this->member))->toBeTrue()
        ->and($this->policy->viewAny($this->outsider))->toBeTrue();
});

test('member can view account they belong to', function () {
    expect($this->policy->view($this->member, $this->account))->toBeTrue();
});

test('admin can view account they belong to', function () {
    expect($this->policy->view($this->admin, $this->account))->toBeTrue();
});

test('outsider cannot view account', function () {
    expect($this->policy->view($this->outsider, $this->account))->toBeFalse();
});

test('admin can update account', function () {
    expect($this->policy->update($this->admin, $this->account))->toBeTrue();
});

test('member cannot update account', function () {
    expect($this->policy->update($this->member, $this->account))->toBeFalse();
});

test('outsider cannot update account', function () {
    expect($this->policy->update($this->outsider, $this->account))->toBeFalse();
});

test('admin can delete account', function () {
    expect($this->policy->delete($this->admin, $this->account))->toBeTrue();
});

test('member cannot delete account', function () {
    expect($this->policy->delete($this->member, $this->account))->toBeFalse();
});

test('admin can manage team', function () {
    expect($this->policy->manageTeam($this->admin, $this->account))->toBeTrue();
});

test('member cannot manage team', function () {
    expect($this->policy->manageTeam($this->member, $this->account))->toBeFalse();
});

test('admin with permission can invite', function () {
    expect($this->policy->invite($this->admin, $this->account))->toBeTrue();
});

test('member cannot invite', function () {
    expect($this->policy->invite($this->member, $this->account))->toBeFalse();
});

test('admin without permission cannot invite', function () {
    $adminWithoutPermission = User::factory()->create();
    $this->account->users()->attach($adminWithoutPermission->id, ['role' => 'admin']);

    expect($this->policy->invite($adminWithoutPermission, $this->account))->toBeFalse();
});

test('admin with permission can remove member', function () {
    expect($this->policy->removeMember($this->admin, $this->account))->toBeTrue();
});

test('member cannot remove members', function () {
    expect($this->policy->removeMember($this->member, $this->account))->toBeFalse();
});

test('admin without permission cannot remove member', function () {
    $adminWithoutPermission = User::factory()->create();
    $this->account->users()->attach($adminWithoutPermission->id, ['role' => 'admin']);

    expect($this->policy->removeMember($adminWithoutPermission, $this->account))->toBeFalse();
});
