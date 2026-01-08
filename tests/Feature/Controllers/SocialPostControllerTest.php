<?php

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create all permissions needed for social post operations
    Permission::findOrCreate('social.manage', 'web');
    Permission::findOrCreate('social.publish', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'social.manage',
        'social.publish',
    ]);
});

function setupUserWithSocialPermissions(User $user): void
{
    $account = $user->accounts()->first();
    if ($account) {
        setPermissionsTeamId($account->id);
        $user->assignRole('admin');
    }
}

test('guests cannot access social posts index', function () {
    $response = $this->get(route('social-posts.index'));

    $response->assertRedirect('/login');
});

test('users with brand can view social posts index', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    SocialPost::factory()->forBrand($brand)->count(3)->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->get(route('social-posts.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Social/Index')
    );
});

test('users can create a social post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->post(route('social-posts.store'), [
        'platform' => SocialPlatform::Instagram->value,
        'format' => SocialFormat::Feed->value,
        'content' => 'This is my social post content!',
        'hashtags' => ['marketing', 'social'],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('social_posts', [
        'brand_id' => $brand->id,
        'platform' => SocialPlatform::Instagram->value,
        'content' => 'This is my social post content!',
    ]);
});

test('social post content is required', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->post(route('social-posts.store'), [
        'platform' => SocialPlatform::Instagram->value,
        'format' => SocialFormat::Feed->value,
    ]);

    $response->assertSessionHasErrors('content');
});

test('users can update a social post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->put(route('social-posts.update', $socialPost), [
        'content' => 'Updated content!',
        'hashtags' => ['updated', 'hashtags'],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('social_posts', [
        'id' => $socialPost->id,
        'content' => 'Updated content!',
    ]);
});

test('users cannot update social posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithSocialPermissions($user);

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $socialPost = SocialPost::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->put(route('social-posts.update', $socialPost), [
        'content' => 'Updated content!',
    ]);

    $response->assertForbidden();
});

test('users can delete a social post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->delete(route('social-posts.destroy', $socialPost));

    $response->assertRedirect();
    $this->assertDatabaseMissing('social_posts', ['id' => $socialPost->id]);
});

test('users can schedule a social post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupUserWithSocialPermissions($user);

    $scheduledAt = now()->addDay()->format('Y-m-d H:i:s');

    $response = $this->actingAs($user)->post(route('social-posts.schedule', $socialPost), [
        'scheduled_at' => $scheduledAt,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('social_posts', [
        'id' => $socialPost->id,
        'status' => SocialPostStatus::Scheduled->value,
    ]);
});

test('users can view social posts queue', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    SocialPost::factory()->forBrand($brand)->queued()->count(2)->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->get(route('social-posts.queue'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Social/Queue')
    );
});

test('users can add social post to queue', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->post(route('social-posts.queue-post', $socialPost));

    $response->assertRedirect();
    $this->assertDatabaseHas('social_posts', [
        'id' => $socialPost->id,
        'status' => SocialPostStatus::Queued->value,
    ]);
});
