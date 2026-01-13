<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\Media;
use App\Models\User;
use App\Policies\MediaPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('media.upload', 'web');
    Permission::findOrCreate('media.delete', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['media.upload', 'media.delete']);

    $this->policy = new MediaPolicy;
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create();
    $this->account->users()->attach($this->user->id, ['role' => 'admin']);
    $this->brand = Brand::factory()->forAccount($this->account)->create();
    $this->media = Media::factory()->forBrand($this->brand)->create();

    session(['current_account_id' => $this->account->id]);
    setPermissionsTeamId($this->account->id);
    $this->user->assignRole('admin');
});

test('user with brand can view any media', function () {
    expect($this->policy->viewAny($this->user))->toBeTrue();
});

test('user without brand cannot view any media', function () {
    $userWithoutBrand = User::factory()->create();

    expect($this->policy->viewAny($userWithoutBrand))->toBeFalse();
});

test('user can view media belonging to their brand', function () {
    expect($this->policy->view($this->user, $this->media))->toBeTrue();
});

test('user cannot view media from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherMedia = Media::factory()->forBrand($otherBrand)->create();

    expect($this->policy->view($this->user, $otherMedia))->toBeFalse();
});

test('user with permission can create media', function () {
    expect($this->policy->create($this->user))->toBeTrue();
});

test('user without permission cannot create media', function () {
    $userWithoutPermission = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($userWithoutPermission->id, ['role' => 'member']);
    Brand::factory()->forAccount($account)->create();
    session(['current_account_id' => $account->id]);

    expect($this->policy->create($userWithoutPermission))->toBeFalse();
});

test('user with permission can update their media', function () {
    expect($this->policy->update($this->user, $this->media))->toBeTrue();
});

test('user cannot update media from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherMedia = Media::factory()->forBrand($otherBrand)->create();

    expect($this->policy->update($this->user, $otherMedia))->toBeFalse();
});

test('user with permission can delete their media', function () {
    expect($this->policy->delete($this->user, $this->media))->toBeTrue();
});

test('user cannot delete media from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherMedia = Media::factory()->forBrand($otherBrand)->create();

    expect($this->policy->delete($this->user, $otherMedia))->toBeFalse();
});

test('user without delete permission cannot delete media', function () {
    $userWithoutPermission = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($userWithoutPermission->id, ['role' => 'member']);
    $brand = Brand::factory()->forAccount($account)->create();
    $media = Media::factory()->forBrand($brand)->create();
    session(['current_account_id' => $account->id]);

    expect($this->policy->delete($userWithoutPermission, $media))->toBeFalse();
});
