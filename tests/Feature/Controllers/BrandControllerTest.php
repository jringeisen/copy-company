<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create permissions and roles needed for brand operations
    Permission::findOrCreate('brands.create', 'web');
    Permission::findOrCreate('brands.update', 'web');
    Permission::findOrCreate('brands.delete', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['brands.create', 'brands.update', 'brands.delete']);
});

test('guests cannot access brand create page', function () {
    $response = $this->get(route('brands.create'));

    $response->assertRedirect('/login');
});

test('authenticated users can view brand create page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('brands.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('Brands/Create'));
});

test('authenticated users can create a brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('brands.store'), [
            'name' => 'My Awesome Brand',
            'slug' => 'my-awesome-brand',
            'tagline' => 'The best brand ever',
            'description' => 'This is a description of my brand.',
            'industry' => 'technology',
            'primary_color' => '#6366f1',
        ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('brands', [
        'account_id' => $account->id,
        'name' => 'My Awesome Brand',
        'slug' => 'my-awesome-brand',
    ]);
});

test('brand slug must be unique', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create(['slug' => 'existing-slug']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('brands.store'), [
            'name' => 'Another Brand',
            'slug' => 'existing-slug',
        ]);

    $response->assertSessionHasErrors('slug');
});

test('brand name is required', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('brands.store'), [
            'slug' => 'test-slug',
        ]);

    $response->assertSessionHasErrors('name');
});

test('brand slug is required', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('brands.store'), [
            'name' => 'Test Brand',
        ]);

    $response->assertSessionHasErrors('slug');
});

test('authenticated users can view brand settings page', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('settings.brand'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/Brand')
        ->has('brand')
    );
});

test('users without brand are redirected to create', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('settings.brand'));

    $response->assertRedirect(route('brands.create'));
});

test('authenticated users can update their brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create(['slug' => 'my-brand']);

    // Set permissions team context
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->put(route('settings.brand.update', $brand), [
            'name' => 'Updated Brand Name',
            'slug' => 'my-brand',
            'tagline' => 'New tagline',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => 'Updated Brand Name',
        'tagline' => 'New tagline',
    ]);
});
