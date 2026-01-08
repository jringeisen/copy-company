<?php

use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create all permissions needed for content sprint operations
    Permission::findOrCreate('sprints.create', 'web');
    Permission::findOrCreate('sprints.manage', 'web');
    Permission::findOrCreate('posts.create', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'sprints.create',
        'sprints.manage',
        'posts.create',
    ]);
});

function setupUserWithSprintPermissions(User $user): void
{
    $account = $user->accounts()->first();
    if ($account) {
        setPermissionsTeamId($account->id);
        $user->assignRole('admin');
    }
}

test('users can view completed sprint with converted indices', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->completed()
        ->withConvertedIdeas([0])
        ->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->get(route('content-sprints.show', $sprint));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('ContentSprint/Show')
        ->has('sprint.converted_indices')
        ->where('sprint.converted_indices', [0])
        ->where('sprint.unconverted_ideas_count', 2)
    );
});

test('accepting ideas records converted indices', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->post(route('content-sprints.accept', $sprint), [
        'idea_indices' => [0, 1],
    ]);

    $response->assertRedirect(route('posts.index'));

    $sprint->refresh();
    expect($sprint->converted_indices)->toBe([0, 1]);
    expect($brand->posts()->count())->toBe(2);
});

test('accepting more ideas merges with existing converted indices', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->completed()
        ->withConvertedIdeas([0])
        ->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->post(route('content-sprints.accept', $sprint), [
        'idea_indices' => [1, 2],
    ]);

    $response->assertRedirect(route('posts.index'));

    $sprint->refresh();
    expect($sprint->converted_indices)->toContain(0)
        ->toContain(1)
        ->toContain(2);
    expect($brand->posts()->count())->toBe(2);
});

test('model helper isIdeaConverted works correctly', function () {
    $sprint = ContentSprint::factory()
        ->completed()
        ->withConvertedIdeas([0, 2])
        ->create();

    expect($sprint->isIdeaConverted(0))->toBeTrue();
    expect($sprint->isIdeaConverted(1))->toBeFalse();
    expect($sprint->isIdeaConverted(2))->toBeTrue();
});

test('unconverted ideas count is calculated correctly', function () {
    $sprint = ContentSprint::factory()
        ->completed()
        ->withConvertedIdeas([0])
        ->create();

    expect($sprint->ideas_count)->toBe(3);
    expect($sprint->unconverted_ideas_count)->toBe(2);
});

test('sprint with no converted ideas has all ideas available', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->get(route('content-sprints.show', $sprint));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('sprint.converted_indices', [])
        ->where('sprint.unconverted_ideas_count', 3)
    );
});
