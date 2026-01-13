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

test('users without brand cannot export subscribers', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('subscribers.export'));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'No brand found.');
});

test('users can update a subscriber', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create(['name' => 'Original Name']);

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->patch(route('subscribers.update', $subscriber), [
            'name' => 'Updated Name',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('subscribers', [
        'id' => $subscriber->id,
        'name' => 'Updated Name',
    ]);
});

test('import skips invalid email addresses', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $csvContent = "email,name\nvalid@example.com,Valid User\ninvalid-email,Invalid User\n,Empty Email";
    $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csvContent);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('subscribers.import'), [
            'file' => $file,
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subscribers', [
        'brand_id' => $brand->id,
        'email' => 'valid@example.com',
    ]);
    expect($brand->subscribers()->count())->toBe(1);
});

test('import skips duplicate emails within same import', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $csvContent = "email,name\ntest@example.com,First\ntest@example.com,Duplicate";
    $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csvContent);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('subscribers.import'), [
            'file' => $file,
        ]);

    $response->assertRedirect();
    expect($brand->subscribers()->count())->toBe(1);
});

test('import skips already existing subscribers', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    // Create existing subscriber
    Subscriber::factory()->forBrand($brand)->create(['email' => 'existing@example.com']);

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $csvContent = "email,name\nexisting@example.com,Existing\nnew@example.com,New User";
    $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csvContent);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('subscribers.import'), [
            'file' => $file,
        ]);

    $response->assertRedirect();
    expect($brand->subscribers()->count())->toBe(2);
});

test('import sanitizes names with formula injection characters', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    // The preg_replace removes the leading = and replaces with '
    $csvContent = "email,name\ntest@example.com,=FORMULA()";
    $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csvContent);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('subscribers.import'), [
            'file' => $file,
        ]);

    $response->assertRedirect();
    // The sanitization replaces the leading = with ', resulting in 'FORMULA()
    $this->assertDatabaseHas('subscribers', [
        'email' => 'test@example.com',
        'name' => "'FORMULA()",
    ]);
});

test('users without brand are redirected from subscribers index', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('subscribers.index'));

    $response->assertRedirect(route('brands.create'));
});

test('export includes subscriber names and handles formula injection', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    // Create confirmed subscribers with names including formula characters
    Subscriber::factory()->forBrand($brand)->confirmed()->create([
        'email' => 'test1@example.com',
        'name' => '=FORMULA()',
    ]);
    Subscriber::factory()->forBrand($brand)->confirmed()->create([
        'email' => 'test2@example.com',
        'name' => '+ATTACK',
    ]);
    Subscriber::factory()->forBrand($brand)->confirmed()->create([
        'email' => 'test3@example.com',
        'name' => null,  // No name
    ]);

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('subscribers.export'));

    $response->assertStatus(200);
    $content = $response->streamedContent();

    // Verify CSV contains sanitized names
    expect($content)->toContain("'FORMULA()");
    expect($content)->toContain("'ATTACK");
    expect($content)->toContain('test1@example.com');
    expect($content)->toContain('test2@example.com');
    expect($content)->toContain('test3@example.com');
});

test('import handles large batches correctly', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    // Create CSV with more than batch size (500) records
    $csvLines = ['email,name'];
    for ($i = 1; $i <= 510; $i++) {
        $csvLines[] = "test{$i}@example.com,User {$i}";
    }
    $csvContent = implode("\n", $csvLines);
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('large_subscribers.csv', $csvContent);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('subscribers.import'), [
            'file' => $file,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify all 510 subscribers were imported
    expect($brand->subscribers()->count())->toBe(510);
});
