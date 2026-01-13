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

test('guests cannot access content sprints index', function () {
    $response = $this->get(route('content-sprints.index'));

    $response->assertRedirect('/login');
});

test('users can view content sprints index', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    ContentSprint::factory()->forBrand($brand)->count(3)->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->get(route('content-sprints.index'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('ContentSprint/Index')
            ->has('sprints', 3)
        );
});

test('user without brand is redirected from index', function () {
    $userWithoutBrand = User::factory()->create();

    $response = $this->actingAs($userWithoutBrand)
        ->get(route('content-sprints.index'));

    $response->assertRedirect(route('brands.create'));
});

test('user can view create sprint page', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->get(route('content-sprints.create'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('ContentSprint/Create')
        );
});

test('user without brand is redirected from create', function () {
    $userWithoutBrand = User::factory()->create();

    $response = $this->actingAs($userWithoutBrand)
        ->get(route('content-sprints.create'));

    $response->assertRedirect(route('brands.create'));
});

test('user can create a content sprint', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->post(route('content-sprints.store'), [
        'topics' => ['marketing', 'social media'],
        'goals' => 'Increase engagement',
        'content_count' => 10,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('content_sprints', [
        'brand_id' => $brand->id,
        'status' => \App\Enums\ContentSprintStatus::Pending->value,
    ]);

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\GenerateContentSprint::class);
});

test('user cannot accept ideas from non-completed sprint', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->create(['status' => \App\Enums\ContentSprintStatus::Generating]);

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->post(route('content-sprints.accept', $sprint), [
        'idea_indices' => [0],
    ]);

    $response->assertRedirect()
        ->assertSessionHas('error', 'Sprint must be completed before accepting ideas.');
});

test('user can retry a failed sprint', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->create(['status' => \App\Enums\ContentSprintStatus::Failed]);

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->post(route('content-sprints.retry', $sprint));

    $response->assertRedirect(route('content-sprints.show', $sprint))
        ->assertSessionHas('success', 'Sprint generation restarted.');

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\GenerateContentSprint::class);
});

test('user cannot retry a non-failed sprint', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->completed()
        ->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->post(route('content-sprints.retry', $sprint));

    $response->assertRedirect()
        ->assertSessionHas('error', 'Only failed sprints can be retried.');
});

test('user can delete a sprint', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()->forBrand($brand)->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->delete(route('content-sprints.destroy', $sprint));

    $response->assertRedirect(route('content-sprints.index'))
        ->assertSessionHas('success', 'Content sprint deleted.');

    $this->assertDatabaseMissing('content_sprints', ['id' => $sprint->id]);
});

test('user cannot delete sprint from another brand', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherSprint = ContentSprint::factory()->forBrand($otherBrand)->create();

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)->delete(route('content-sprints.destroy', $otherSprint));

    $response->assertForbidden();
});

test('sprint show paginates ideas', function () {
    $ideas = [];
    for ($i = 0; $i < 15; $i++) {
        $ideas[] = ['title' => "Idea {$i}", 'description' => "Description {$i}"];
    }

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->create([
            'status' => \App\Enums\ContentSprintStatus::Completed,
            'generated_content' => $ideas,
        ]);

    setupUserWithSprintPermissions($user);

    $response = $this->actingAs($user)
        ->get(route('content-sprints.show', ['content_sprint' => $sprint, 'page' => 2]));

    $response->assertOk();
});
