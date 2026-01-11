<?php

use App\Enums\SocialPostStatus;
use App\Http\Resources\SocialPostResource;
use App\Models\Account;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('social.manage', 'web');
    Permission::findOrCreate('social.publish', 'web');
    Permission::findOrCreate('brands.create', 'web');
    Permission::findOrCreate('brands.update', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'social.manage',
        'social.publish',
        'brands.create',
        'brands.update',
    ]);
});

function setupTimezoneTestUser(User $user): void
{
    $account = $user->accounts()->first();
    if ($account) {
        setPermissionsTeamId($account->id);
        $user->assignRole('admin');
    }
}

// Brand Timezone Tests

test('brand can be created with timezone', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('brands.store'), [
            'name' => 'Pacific Brand',
            'slug' => 'pacific-brand',
            'timezone' => 'America/Los_Angeles',
        ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('brands', [
        'account_id' => $account->id,
        'name' => 'Pacific Brand',
        'timezone' => 'America/Los_Angeles',
    ]);
});

test('brand timezone defaults to America/New_York when not provided', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('brands.store'), [
            'name' => 'Default Timezone Brand',
            'slug' => 'default-timezone-brand',
        ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('brands', [
        'name' => 'Default Timezone Brand',
        'timezone' => 'America/New_York',
    ]);
});

test('brand timezone can be updated', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create([
        'slug' => 'my-brand',
        'timezone' => 'America/New_York',
    ]);

    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->put(route('settings.brand.update', $brand), [
            'name' => $brand->name,
            'slug' => $brand->slug,
            'timezone' => 'America/Los_Angeles',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'timezone' => 'America/Los_Angeles',
    ]);
});

test('brand timezone must be valid', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->post(route('brands.store'), [
            'name' => 'Invalid Timezone Brand',
            'slug' => 'invalid-timezone-brand',
            'timezone' => 'Invalid/Timezone',
        ]);

    $response->assertSessionHasErrors('timezone');
});

// Social Post Scheduling Timezone Tests

test('scheduling a post converts brand timezone to UTC', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create([
        'timezone' => 'America/Los_Angeles', // Pacific Time
    ]);
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupTimezoneTestUser($user);

    // Schedule for 2:00 PM Pacific Time tomorrow
    $pacificTime = Carbon::now('America/Los_Angeles')->addDay()->setTime(14, 0, 0);
    $expectedUtc = $pacificTime->copy()->setTimezone('UTC');

    $response = $this->actingAs($user)->post(route('social-posts.schedule', $socialPost), [
        'scheduled_at' => $pacificTime->format('Y-m-d\TH:i'),
    ]);

    $response->assertRedirect();

    $socialPost->refresh();
    expect($socialPost->status)->toBe(SocialPostStatus::Scheduled);
    // The stored time should be in UTC
    expect($socialPost->scheduled_at->format('Y-m-d H:i'))->toBe($expectedUtc->format('Y-m-d H:i'));
});

test('bulk scheduling converts brand timezone to UTC', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create([
        'timezone' => 'America/Chicago', // Central Time
    ]);
    $posts = SocialPost::factory()->forBrand($brand)->queued()->count(3)->create();

    setupTimezoneTestUser($user);

    // Schedule for 3:00 PM Central Time tomorrow
    $centralTime = Carbon::now('America/Chicago')->addDay()->setTime(15, 0, 0);
    $expectedUtc = $centralTime->copy()->setTimezone('UTC');

    $response = $this->actingAs($user)->post(route('social-posts.bulk-schedule'), [
        'social_post_ids' => $posts->pluck('id')->toArray(),
        'scheduled_at' => $centralTime->format('Y-m-d\TH:i'),
        'interval_minutes' => 30,
    ]);

    $response->assertRedirect();

    // First post should be at the exact scheduled time (in UTC)
    $firstPost = $posts->first()->fresh();
    expect($firstPost->scheduled_at->format('Y-m-d H:i'))->toBe($expectedUtc->format('Y-m-d H:i'));

    // Second post should be 30 minutes later
    $secondPost = $posts->skip(1)->first()->fresh();
    expect($secondPost->scheduled_at->format('Y-m-d H:i'))->toBe($expectedUtc->addMinutes(30)->format('Y-m-d H:i'));
});

// Social Post Resource Timezone Tests

test('social post resource converts scheduled_at to brand timezone', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create([
        'timezone' => 'America/Los_Angeles',
    ]);

    // Create a post scheduled for 10:00 PM UTC
    $utcTime = Carbon::create(2025, 6, 15, 22, 0, 0, 'UTC');
    $socialPost = SocialPost::factory()->forBrand($brand)->create([
        'status' => SocialPostStatus::Scheduled,
        'scheduled_at' => $utcTime,
    ]);

    $resource = (new SocialPostResource($socialPost))->resolve();

    // 10:00 PM UTC = 3:00 PM Pacific (during PDT)
    // The display format should show the time in Pacific
    expect($resource['scheduled_at'])->toContain('3:00 PM');
    expect($resource['scheduled_at_form'])->toBe('2025-06-15T15:00');
});

test('social post resource handles different timezones correctly', function () {
    $user = User::factory()->create();

    // Test with Tokyo timezone (UTC+9)
    $brand = Brand::factory()->forUser($user)->create([
        'timezone' => 'Asia/Tokyo',
    ]);

    // Create a post scheduled for 12:00 PM UTC
    $utcTime = Carbon::create(2025, 6, 15, 12, 0, 0, 'UTC');
    $socialPost = SocialPost::factory()->forBrand($brand)->create([
        'status' => SocialPostStatus::Scheduled,
        'scheduled_at' => $utcTime,
    ]);

    $resource = (new SocialPostResource($socialPost))->resolve();

    // 12:00 PM UTC = 9:00 PM Tokyo (UTC+9)
    expect($resource['scheduled_at'])->toContain('9:00 PM');
    expect($resource['scheduled_at_form'])->toBe('2025-06-15T21:00');
});

test('social post resource uses brand timezone correctly for Eastern timezone', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create([
        'timezone' => 'America/New_York',
    ]);

    $utcTime = Carbon::create(2025, 6, 15, 18, 0, 0, 'UTC');
    $socialPost = SocialPost::factory()->forBrand($brand)->create([
        'status' => SocialPostStatus::Scheduled,
        'scheduled_at' => $utcTime,
    ]);

    $resource = (new SocialPostResource($socialPost))->resolve();

    // 6:00 PM UTC = 2:00 PM Eastern (during EDT)
    expect($resource['scheduled_at'])->toContain('2:00 PM');
    expect($resource['scheduled_at_form'])->toBe('2025-06-15T14:00');
});
