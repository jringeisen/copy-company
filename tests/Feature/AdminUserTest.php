<?php

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Admin Users Index Tests

test('admin users index page requires authentication', function () {
    $response = $this->get('/admin/users');

    $response->assertRedirect('/login');
});

test('admin users index page requires admin access', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/admin/users');

    $response->assertForbidden();
});

test('admin can access users index page', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/admin/users');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users')
        ->has('pagination')
        ->has('stats')
        ->has('filters')
    );
});

test('admin users index returns all users with correct props', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/admin/users');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users', 4)
        ->where('stats.total', 4)
    );
});

test('admin users index supports search filtering', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com', 'name' => 'Admin User']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/admin/users?search=John');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users', 1)
    );
});

// Impersonation Start Tests

test('admin can impersonate a user', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    $targetUser = User::factory()->create();

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->post("/admin/impersonate/{$targetUser->id}");

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($targetUser);
    expect(session('impersonating_from'))->toBe($admin->id);
});

test('admin cannot impersonate self', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->post("/admin/impersonate/{$admin->id}");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You cannot impersonate yourself.');
    $this->assertAuthenticatedAs($admin);
});

test('admin cannot chain impersonation', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    $targetUser1 = User::factory()->create();
    $targetUser2 = User::factory()->create();

    $this->actingAs($admin)
        ->withSession([
            'current_account_id' => $account->id,
            'impersonating_from' => $admin->id,
        ]);
    $response = $this->post("/admin/impersonate/{$targetUser2->id}");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You are already impersonating a user. Stop the current session first.');
});

test('non-admin cannot start impersonation', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $targetUser = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->post("/admin/impersonate/{$targetUser->id}");

    $response->assertForbidden();
});

// Impersonation Stop Tests

test('impersonation stop restores admin user', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    $targetUser = User::factory()->create();

    $this->actingAs($targetUser)
        ->withSession(['impersonating_from' => $admin->id]);
    $response = $this->post('/admin/impersonate/stop');

    $response->assertRedirect(route('admin.users.index'));
    $this->assertAuthenticatedAs($admin);
    expect(session('impersonating_from'))->toBeNull();
});

test('impersonation stop handles no active session', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $response = $this->post('/admin/impersonate/stop');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error', 'No active impersonation session.');
});

// Shared Props Tests

test('is_admin shared prop is true for admin users', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('auth.is_admin', true)
    );
});

test('is_admin shared prop is false for non-admin users', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('auth.is_admin', false)
    );
});

test('impersonating shared prop is present during impersonation', function () {
    $admin = User::factory()->create(['name' => 'Admin Person']);
    $targetUser = User::factory()->create(['name' => 'Target Person']);
    $account = Account::factory()->create();
    $targetUser->accounts()->attach($account->id, ['role' => 'admin']);

    $this->actingAs($targetUser)
        ->withSession([
            'impersonating_from' => $admin->id,
            'current_account_id' => $account->id,
        ]);
    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('impersonating.admin_id', $admin->id)
        ->where('impersonating.admin_name', 'Admin Person')
        ->where('impersonating.user_name', 'Target Person')
    );
});

test('impersonating shared prop is null when not impersonating', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('impersonating', null)
    );
});
