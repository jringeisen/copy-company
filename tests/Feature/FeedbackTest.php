<?php

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use App\Models\Account;
use App\Models\Brand;
use App\Models\Feedback;
use App\Models\User;
use App\Notifications\FeedbackReceivedNotification;
use App\Notifications\FeedbackStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Helper to set up a standard user with account and brand
function createUserWithBrand(): array
{
    $account = Account::factory()->create();
    $user = User::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    return [$user, $account, $brand];
}

// Helper to set up an admin user
function createAdmin(): array
{
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $admin = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($admin->id, ['role' => 'admin']);

    return [$admin, $account];
}

// === User Feedback Submission ===

test('unauthenticated user cannot submit feedback', function () {
    $response = $this->post('/feedback', [
        'type' => 'bug',
        'priority' => 'medium',
        'description' => 'This is a test feedback submission',
        'page_url' => 'https://example.com/dashboard',
    ]);

    $response->assertRedirect('/login');
});

test('unauthenticated user cannot view feedback page', function () {
    $response = $this->get('/feedback');

    $response->assertRedirect('/login');
});

test('user can submit feedback without screenshot', function () {
    Notification::fake();

    [$user, $account, $brand] = createUserWithBrand();

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->post('/feedback', [
        'type' => 'bug',
        'priority' => 'high',
        'description' => 'There is a bug on the dashboard page',
        'page_url' => 'https://example.com/dashboard',
        'user_agent' => 'Mozilla/5.0',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('feedback', [
        'user_id' => $user->id,
        'brand_id' => $brand->id,
        'type' => 'bug',
        'priority' => 'high',
        'status' => 'open',
        'description' => 'There is a bug on the dashboard page',
        'page_url' => 'https://example.com/dashboard',
        'screenshot_path' => null,
    ]);
});

test('user can submit feedback with screenshot', function () {
    Notification::fake();
    Storage::fake('s3');

    [$user, $account, $brand] = createUserWithBrand();

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->post('/feedback', [
        'type' => 'feature_request',
        'priority' => 'medium',
        'description' => 'Please add a dark mode feature',
        'page_url' => 'https://example.com/settings',
        'user_agent' => 'Mozilla/5.0',
        'screenshot' => UploadedFile::fake()->image('screenshot.png', 800, 600),
    ]);

    $response->assertRedirect();

    $feedback = Feedback::query()->where('user_id', $user->id)->first();
    expect($feedback)->not->toBeNull();
    expect($feedback->screenshot_path)->not->toBeNull();
    expect($feedback->type)->toBe(FeedbackType::FeatureRequest);
    expect($feedback->priority)->toBe(FeedbackPriority::Medium);

    Storage::disk('s3')->assertExists('feedback/'.$feedback->screenshot_path);
});

test('feedback submission captures current brand', function () {
    Notification::fake();

    [$user, $account, $brand] = createUserWithBrand();

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $this->post('/feedback', [
        'type' => 'bug',
        'priority' => 'low',
        'description' => 'This bug only happens on this brand',
        'page_url' => 'https://example.com/posts',
        'user_agent' => 'Mozilla/5.0',
    ]);

    $feedback = Feedback::query()->where('user_id', $user->id)->first();
    expect($feedback->brand_id)->toBe($brand->id);
});

test('feedback submission notifies admins', function () {
    Notification::fake();

    config(['admin.emails' => ['admin@platform.com']]);
    $adminUser = User::factory()->create(['email' => 'admin@platform.com']);

    [$user, $account, $brand] = createUserWithBrand();

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $this->post('/feedback', [
        'type' => 'bug',
        'priority' => 'critical',
        'description' => 'Critical bug needs immediate attention',
        'page_url' => 'https://example.com/dashboard',
        'user_agent' => 'Mozilla/5.0',
    ]);

    Notification::assertSentTo($adminUser, FeedbackReceivedNotification::class);
});

test('feedback description must be at least 10 characters', function () {
    [$user, $account] = [User::factory()->create(), Account::factory()->create()];
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->post('/feedback', [
        'type' => 'bug',
        'priority' => 'medium',
        'description' => 'Short',
        'page_url' => 'https://example.com/dashboard',
    ]);

    $response->assertSessionHasErrors('description');
});

test('feedback type must be valid enum value', function () {
    [$user, $account] = [User::factory()->create(), Account::factory()->create()];
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->post('/feedback', [
        'type' => 'invalid_type',
        'priority' => 'medium',
        'description' => 'This is a valid description for testing',
        'page_url' => 'https://example.com/dashboard',
    ]);

    $response->assertSessionHasErrors('type');
});

test('feedback screenshot must be an image', function () {
    [$user, $account] = [User::factory()->create(), Account::factory()->create()];
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->post('/feedback', [
        'type' => 'bug',
        'priority' => 'medium',
        'description' => 'This is a valid description for testing',
        'page_url' => 'https://example.com/dashboard',
        'screenshot' => UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('screenshot');
});

// === User Feedback History ===

test('user can view their feedback history', function () {
    [$user, $account, $brand] = createUserWithBrand();

    Feedback::factory()->count(3)->create(['user_id' => $user->id, 'brand_id' => $brand->id]);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/feedback');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Feedback/Index')
        ->has('feedback', 3)
        ->has('pagination')
        ->has('statuses')
    );
});

test('user only sees their own feedback', function () {
    [$user, $account, $brand] = createUserWithBrand();
    $otherUser = User::factory()->create();

    Feedback::factory()->count(2)->create(['user_id' => $user->id, 'brand_id' => $brand->id]);
    Feedback::factory()->count(3)->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/feedback');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Feedback/Index')
        ->has('feedback', 2)
    );
});

test('user can filter feedback by status', function () {
    [$user, $account, $brand] = createUserWithBrand();

    Feedback::factory()->count(2)->create(['user_id' => $user->id, 'brand_id' => $brand->id, 'status' => FeedbackStatus::Open]);
    Feedback::factory()->count(1)->resolved()->create(['user_id' => $user->id, 'brand_id' => $brand->id]);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/feedback?status=resolved');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Feedback/Index')
        ->has('feedback', 1)
    );
});

// === Admin Feedback Management ===

test('non-admin cannot access admin feedback page', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/admin/feedback');

    $response->assertForbidden();
});

test('admin can view all feedback', function () {
    [$admin, $account] = createAdmin();

    Feedback::factory()->count(5)->create();

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/admin/feedback');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Feedback/Index')
        ->has('feedback', 5)
        ->has('pagination')
        ->has('stats')
        ->has('filters')
        ->has('types')
        ->has('priorities')
        ->has('statuses')
    );
});

test('admin feedback index shows correct stats', function () {
    [$admin, $account] = createAdmin();

    Feedback::factory()->count(3)->create(['status' => FeedbackStatus::Open]);
    Feedback::factory()->count(2)->create(['status' => FeedbackStatus::InProgress]);
    Feedback::factory()->count(1)->resolved()->create();

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/admin/feedback');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Feedback/Index')
        ->where('stats.total', 6)
        ->where('stats.open', 3)
        ->where('stats.in_progress', 2)
        ->where('stats.resolved', 1)
    );
});

test('admin can filter feedback by status', function () {
    [$admin, $account] = createAdmin();

    Feedback::factory()->count(3)->create(['status' => FeedbackStatus::Open]);
    Feedback::factory()->count(2)->create(['status' => FeedbackStatus::InProgress]);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/admin/feedback?status=in_progress');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Feedback/Index')
        ->has('feedback', 2)
    );
});

test('admin can filter feedback by type', function () {
    [$admin, $account] = createAdmin();

    Feedback::factory()->count(3)->create(['type' => FeedbackType::Bug]);
    Feedback::factory()->count(2)->create(['type' => FeedbackType::FeatureRequest]);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/admin/feedback?type=bug');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Feedback/Index')
        ->has('feedback', 3)
    );
});

test('admin can filter feedback by priority', function () {
    [$admin, $account] = createAdmin();

    Feedback::factory()->count(2)->create(['priority' => FeedbackPriority::Critical]);
    Feedback::factory()->count(3)->create(['priority' => FeedbackPriority::Low]);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/admin/feedback?priority=critical');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Feedback/Index')
        ->has('feedback', 2)
    );
});

test('admin can search feedback by description', function () {
    [$admin, $account] = createAdmin();

    Feedback::factory()->create(['description' => 'The dashboard is broken']);
    Feedback::factory()->create(['description' => 'Please add dark mode']);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get('/admin/feedback?search=dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Feedback/Index')
        ->has('feedback', 1)
    );
});

test('admin can view single feedback detail', function () {
    [$admin, $account] = createAdmin();

    $feedback = Feedback::factory()->create();

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get("/admin/feedback/{$feedback->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Feedback/Show')
        ->has('feedback')
        ->has('statuses')
    );
});

test('non-admin cannot view admin feedback detail', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $feedback = Feedback::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->get("/admin/feedback/{$feedback->id}");

    $response->assertForbidden();
});

// === Admin Feedback Updates ===

test('admin can update feedback status', function () {
    Notification::fake();

    [$admin, $account] = createAdmin();

    $feedback = Feedback::factory()->create(['status' => FeedbackStatus::Open]);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->put("/admin/feedback/{$feedback->id}", [
        'status' => 'in_progress',
        'admin_notes' => 'We are looking into this issue.',
    ]);

    $response->assertRedirect();

    $feedback->refresh();
    expect($feedback->status)->toBe(FeedbackStatus::InProgress);
    expect($feedback->admin_notes)->toBe('We are looking into this issue.');
});

test('admin can update feedback status to resolved and sets resolved_at', function () {
    Notification::fake();

    [$admin, $account] = createAdmin();

    $feedback = Feedback::factory()->create(['status' => FeedbackStatus::Open]);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->put("/admin/feedback/{$feedback->id}", [
        'status' => 'resolved',
        'admin_notes' => 'This has been fixed.',
    ]);

    $response->assertRedirect();

    $feedback->refresh();
    expect($feedback->status)->toBe(FeedbackStatus::Resolved);
    expect($feedback->resolved_at)->not->toBeNull();
});

test('status change notifies the feedback submitter', function () {
    Notification::fake();

    [$admin, $account] = createAdmin();

    $submitter = User::factory()->create();
    $feedback = Feedback::factory()->create([
        'user_id' => $submitter->id,
        'status' => FeedbackStatus::Open,
    ]);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $this->put("/admin/feedback/{$feedback->id}", [
        'status' => 'resolved',
        'admin_notes' => 'This issue has been resolved.',
    ]);

    Notification::assertSentTo($submitter, FeedbackStatusUpdatedNotification::class);
});

test('no notification sent when status does not change', function () {
    Notification::fake();

    [$admin, $account] = createAdmin();

    $submitter = User::factory()->create();
    $feedback = Feedback::factory()->create([
        'user_id' => $submitter->id,
        'status' => FeedbackStatus::Open,
    ]);

    $this->actingAs($admin)
        ->withSession(['current_account_id' => $account->id]);

    $this->put("/admin/feedback/{$feedback->id}", [
        'status' => 'open',
        'admin_notes' => 'Adding notes without status change.',
    ]);

    Notification::assertNotSentTo($submitter, FeedbackStatusUpdatedNotification::class);
});

test('non-admin cannot update feedback status', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $feedback = Feedback::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);

    $response = $this->put("/admin/feedback/{$feedback->id}", [
        'status' => 'resolved',
    ]);

    $response->assertForbidden();
});
