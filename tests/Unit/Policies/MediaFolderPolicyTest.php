<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\MediaFolder;
use App\Models\User;
use App\Policies\MediaFolderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new MediaFolderPolicy;
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create();
    $this->account->users()->attach($this->user->id, ['role' => 'admin']);
    $this->brand = Brand::factory()->forAccount($this->account)->create();
    $this->folder = MediaFolder::factory()->forBrand($this->brand)->create();

    session(['current_account_id' => $this->account->id]);
});

test('user with brand can view any folders', function () {
    expect($this->policy->viewAny($this->user))->toBeTrue();
});

test('user without brand cannot view any folders', function () {
    $userWithoutBrand = User::factory()->create();

    expect($this->policy->viewAny($userWithoutBrand))->toBeFalse();
});

test('user can view folder belonging to their brand', function () {
    expect($this->policy->view($this->user, $this->folder))->toBeTrue();
});

test('user cannot view folder from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherFolder = MediaFolder::factory()->forBrand($otherBrand)->create();

    expect($this->policy->view($this->user, $otherFolder))->toBeFalse();
});

test('user with brand can create folders', function () {
    expect($this->policy->create($this->user))->toBeTrue();
});

test('user without brand cannot create folders', function () {
    $userWithoutBrand = User::factory()->create();

    expect($this->policy->create($userWithoutBrand))->toBeFalse();
});

test('user can update folder belonging to their brand', function () {
    expect($this->policy->update($this->user, $this->folder))->toBeTrue();
});

test('user cannot update folder from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherFolder = MediaFolder::factory()->forBrand($otherBrand)->create();

    expect($this->policy->update($this->user, $otherFolder))->toBeFalse();
});

test('user can delete folder belonging to their brand', function () {
    expect($this->policy->delete($this->user, $this->folder))->toBeTrue();
});

test('user cannot delete folder from another brand', function () {
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherFolder = MediaFolder::factory()->forBrand($otherBrand)->create();

    expect($this->policy->delete($this->user, $otherFolder))->toBeFalse();
});
