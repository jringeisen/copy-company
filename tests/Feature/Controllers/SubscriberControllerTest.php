<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create all permissions needed for subscriber operations
    Permission::findOrCreate('brands.update', 'web');
    Permission::findOrCreate('subscribers.view', 'web');
    Permission::findOrCreate('subscribers.export', 'web');
    Permission::findOrCreate('subscribers.delete', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'brands.update',
        'subscribers.view',
        'subscribers.export',
        'subscribers.delete',
    ]);
});

test('guests cannot access subscribers index', function () {
    $response = $this->get(route('subscribers.index'));

    $response->assertRedirect('/login');
});

test('users with brand can view subscribers index', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    Subscriber::factory()->forBrand($brand)->count(5)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('subscribers.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Subscribers/Index')
    );
});

test('users can delete a subscriber', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->delete(route('subscribers.destroy', $subscriber));

    $response->assertRedirect();
    $this->assertDatabaseMissing('subscribers', ['id' => $subscriber->id]);
});

test('users cannot delete subscribers from other brands', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->delete(route('subscribers.destroy', $subscriber));

    $response->assertForbidden();
    $this->assertDatabaseHas('subscribers', ['id' => $subscriber->id]);
});

test('users can export subscribers as csv', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    Subscriber::factory()->forBrand($brand)->count(3)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('subscribers.export'));

    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

test('users can import subscribers from csv', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $csvContent = "email,name\ntest1@example.com,John Doe\ntest2@example.com,Jane Smith";
    $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csvContent);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('subscribers.import'), [
            'file' => $file,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subscribers', [
        'brand_id' => $brand->id,
        'email' => 'test1@example.com',
        'name' => 'John Doe',
    ]);
    $this->assertDatabaseHas('subscribers', [
        'brand_id' => $brand->id,
        'email' => 'test2@example.com',
        'name' => 'Jane Smith',
    ]);
});
