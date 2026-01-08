<?php

use App\Mail\AccountInvitationMail;
use App\Models\Account;
use App\Models\AccountInvitation;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    // Create permissions
    $permissions = [
        'brands.create', 'brands.update', 'brands.delete',
        'posts.create', 'posts.update', 'posts.delete', 'posts.publish',
        'subscribers.view', 'subscribers.export', 'subscribers.delete',
        'social.manage', 'social.publish',
        'media.upload', 'media.delete',
        'sprints.create', 'sprints.manage',
        'settings.brand', 'settings.email-domain', 'settings.social',
        'team.invite', 'team.manage', 'team.remove',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    // Create roles
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());

    $memberRole = Role::findOrCreate('member', 'web');
    $memberRole->syncPermissions([
        'brands.update', 'posts.create', 'posts.update', 'posts.delete', 'posts.publish',
        'subscribers.view', 'social.manage', 'social.publish',
        'media.upload', 'media.delete', 'sprints.create', 'sprints.manage',
        'settings.brand', 'settings.social',
    ]);

    $viewerRole = Role::findOrCreate('viewer', 'web');
    $viewerRole->syncPermissions(['subscribers.view']);
});

/**
 * Helper to set up an admin user with account and brand
 */
function setupAdmin(): array
{
    $admin = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $admin->assignRole('admin');

    return [$admin, $account, $brand];
}

// ============================================
// STORE (SEND INVITATION) TESTS
// ============================================

test('guest cannot send invitations', function () {
    $response = $this->post('/settings/team/invite', [
        'email' => 'test@example.com',
        'role' => 'member',
    ]);

    $response->assertRedirect('/login');
});

test('admin can send invitation', function () {
    [$admin, $account] = setupAdmin();

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'newuser@example.com',
        'role' => 'member',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('account_invitations', [
        'account_id' => $account->id,
        'email' => 'newuser@example.com',
        'role' => 'member',
    ]);

    Mail::assertSent(AccountInvitationMail::class, function ($mail) {
        return $mail->hasTo('newuser@example.com');
    });
});

test('admin can invite as admin role', function () {
    [$admin, $account] = setupAdmin();

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'newadmin@example.com',
        'role' => 'admin',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('account_invitations', [
        'email' => 'newadmin@example.com',
        'role' => 'admin',
    ]);
});

test('admin can invite as viewer role', function () {
    [$admin, $account] = setupAdmin();

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'newviewer@example.com',
        'role' => 'viewer',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('account_invitations', [
        'email' => 'newviewer@example.com',
        'role' => 'viewer',
    ]);
});

test('member cannot send invitations', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);
    $account->users()->attach($member->id, ['role' => 'member']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $member->assignRole('member');

    $response = $this->actingAs($member)->post('/settings/team/invite', [
        'email' => 'test@example.com',
        'role' => 'member',
    ]);

    $response->assertForbidden();
});

test('viewer cannot send invitations', function () {
    $admin = User::factory()->create();
    $viewer = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);
    $account->users()->attach($viewer->id, ['role' => 'viewer']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $viewer->assignRole('viewer');

    $response = $this->actingAs($viewer)->post('/settings/team/invite', [
        'email' => 'test@example.com',
        'role' => 'member',
    ]);

    $response->assertForbidden();
});

test('cannot invite existing account member', function () {
    [$admin, $account] = setupAdmin();

    $existingMember = User::factory()->create(['email' => 'existing@example.com']);
    $account->users()->attach($existingMember->id, ['role' => 'member']);

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'existing@example.com',
        'role' => 'member',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('cannot send duplicate pending invitation', function () {
    [$admin, $account] = setupAdmin();

    AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'pending@example.com',
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'pending@example.com',
        'role' => 'member',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('can resend invitation after expiry', function () {
    [$admin, $account] = setupAdmin();

    AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'expired@example.com',
        'expires_at' => now()->subDay(),
        'accepted_at' => null,
    ]);

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'expired@example.com',
        'role' => 'member',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('email is required for invitation', function () {
    [$admin] = setupAdmin();

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'role' => 'member',
    ]);

    $response->assertSessionHasErrors('email');
});

test('email must be valid for invitation', function () {
    [$admin] = setupAdmin();

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'not-an-email',
        'role' => 'member',
    ]);

    $response->assertSessionHasErrors('email');
});

test('role is required for invitation', function () {
    [$admin] = setupAdmin();

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasErrors('role');
});

test('role must be valid for invitation', function () {
    [$admin] = setupAdmin();

    $response = $this->actingAs($admin)->post('/settings/team/invite', [
        'email' => 'test@example.com',
        'role' => 'superuser',
    ]);

    $response->assertSessionHasErrors('role');
});

// ============================================
// ACCEPT INVITATION TESTS
// ============================================

test('existing user can accept invitation', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'existing@example.com',
        'role' => 'member',
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    $response = $this->get("/invitations/{$invitation->token}");

    $response->assertRedirect('/dashboard');
    $response->assertSessionHas('success');

    expect($account->hasMember($existingUser))->toBeTrue();
    expect($account->getUserRole($existingUser))->toBe('member');

    $invitation->refresh();
    expect($invitation->isAccepted())->toBeTrue();
});

test('new user is redirected to register with invitation', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'newuser@example.com',
        'role' => 'member',
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    $response = $this->get("/invitations/{$invitation->token}");

    $response->assertRedirect('/register');
    expect(session('invitation_token'))->toBe($invitation->token);
});

test('invalid invitation token shows error', function () {
    $response = $this->get('/invitations/invalid-token-here');

    $response->assertRedirect('/login');
    $response->assertSessionHas('error');
});

test('expired invitation shows error', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'test@example.com',
        'expires_at' => now()->subDay(),
        'accepted_at' => null,
    ]);

    $response = $this->get("/invitations/{$invitation->token}");

    $response->assertRedirect('/login');
    $response->assertSessionHas('error');
});

test('already accepted invitation shows error', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'test@example.com',
        'expires_at' => now()->addDays(7),
        'accepted_at' => now()->subHour(),
    ]);

    $response = $this->get("/invitations/{$invitation->token}");

    $response->assertRedirect('/login');
    $response->assertSessionHas('error');
});

test('user already in account sees info message', function () {
    $account = Account::factory()->create();
    $admin = User::factory()->create();
    $existingMember = User::factory()->create(['email' => 'member@example.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);
    $account->users()->attach($existingMember->id, ['role' => 'member']);
    Brand::factory()->forAccount($account)->create();

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'member@example.com',
        'role' => 'viewer',
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    $response = $this->actingAs($existingMember)->get("/invitations/{$invitation->token}");

    $response->assertRedirect('/dashboard');
    $response->assertSessionHas('info');
});

// ============================================
// DESTROY (CANCEL) INVITATION TESTS
// ============================================

test('admin can cancel invitation', function () {
    [$admin, $account] = setupAdmin();

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'pending@example.com',
        'expires_at' => now()->addDays(7),
    ]);

    $response = $this->actingAs($admin)
        ->delete("/settings/team/invitations/{$invitation->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('account_invitations', [
        'id' => $invitation->id,
    ]);
});

test('member cannot cancel invitations', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);
    $account->users()->attach($member->id, ['role' => 'member']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $member->assignRole('member');

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'pending@example.com',
    ]);

    $response = $this->actingAs($member)
        ->delete("/settings/team/invitations/{$invitation->id}");

    $response->assertForbidden();
});

test('admin cannot cancel invitation from different account', function () {
    [$admin, $account] = setupAdmin();

    $otherAccount = Account::factory()->create();
    $otherAdmin = User::factory()->create();
    $otherAccount->users()->attach($otherAdmin->id, ['role' => 'admin']);

    $invitation = AccountInvitation::factory()->forAccount($otherAccount)->create([
        'invited_by' => $otherAdmin->id,
        'email' => 'pending@example.com',
    ]);

    $response = $this->actingAs($admin)
        ->delete("/settings/team/invitations/{$invitation->id}");

    $response->assertNotFound();
});

// ============================================
// RESEND INVITATION TESTS
// ============================================

test('admin can resend invitation', function () {
    [$admin, $account] = setupAdmin();

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'pending@example.com',
        'expires_at' => now()->addDay(),
        'token' => 'old-token',
    ]);

    $oldToken = $invitation->token;

    $response = $this->actingAs($admin)
        ->post("/settings/team/invitations/{$invitation->id}/resend");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $invitation->refresh();
    expect($invitation->token)->not->toBe($oldToken);
    expect($invitation->expires_at->gt(now()->addDays(6)))->toBeTrue();

    Mail::assertSent(AccountInvitationMail::class);
});

test('member cannot resend invitations', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($admin->id, ['role' => 'admin']);
    $account->users()->attach($member->id, ['role' => 'member']);
    Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $member->assignRole('member');

    $invitation = AccountInvitation::factory()->forAccount($account)->create([
        'invited_by' => $admin->id,
        'email' => 'pending@example.com',
    ]);

    $response = $this->actingAs($member)
        ->post("/settings/team/invitations/{$invitation->id}/resend");

    $response->assertForbidden();
});

test('admin cannot resend invitation from different account', function () {
    [$admin, $account] = setupAdmin();

    $otherAccount = Account::factory()->create();
    $otherAdmin = User::factory()->create();
    $otherAccount->users()->attach($otherAdmin->id, ['role' => 'admin']);

    $invitation = AccountInvitation::factory()->forAccount($otherAccount)->create([
        'invited_by' => $otherAdmin->id,
        'email' => 'pending@example.com',
    ]);

    $response = $this->actingAs($admin)
        ->post("/settings/team/invitations/{$invitation->id}/resend");

    $response->assertNotFound();
});
