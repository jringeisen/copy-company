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

test('users can filter social posts by platform', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    SocialPost::factory()->forBrand($brand)->forPlatform(SocialPlatform::Instagram)->count(2)->create();
    SocialPost::factory()->forBrand($brand)->forPlatform(SocialPlatform::Facebook)->count(3)->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->get(route('social-posts.index', ['platform' => 'instagram']));

    $response->assertStatus(200);
});

test('users can filter social posts by status', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    SocialPost::factory()->forBrand($brand)->draft()->count(2)->create();
    SocialPost::factory()->forBrand($brand)->published()->count(3)->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->get(route('social-posts.index', ['status' => 'draft']));

    $response->assertStatus(200);
});

test('cannot queue a non-draft social post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->published()->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->post(route('social-posts.queue-post', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Only draft posts can be queued.');
});

test('cannot schedule a published social post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->published()->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->post(route('social-posts.schedule', $socialPost), [
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'This post cannot be scheduled.');
});

test('users can bulk schedule social posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post1 = SocialPost::factory()->forBrand($brand)->draft()->create();
    $post2 = SocialPost::factory()->forBrand($brand)->queued()->create();

    setupUserWithSocialPermissions($user);

    $scheduledAt = now()->addDay()->format('Y-m-d H:i:s');

    $response = $this->actingAs($user)->post(route('social-posts.bulk-schedule'), [
        'social_post_ids' => [$post1->id, $post2->id],
        'scheduled_at' => $scheduledAt,
        'interval_minutes' => 30,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('social_posts', [
        'id' => $post1->id,
        'status' => SocialPostStatus::Scheduled->value,
    ]);
    $this->assertDatabaseHas('social_posts', [
        'id' => $post2->id,
        'status' => SocialPostStatus::Scheduled->value,
    ]);
});

test('cannot retry a non-failed social post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->post(route('social-posts.retry', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Only failed posts can be retried.');
});

test('users without brand are redirected from social posts index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('social-posts.index'));

    $response->assertRedirect(route('brands.create'));
});

test('users without brand are redirected from social posts queue', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('social-posts.queue'));

    $response->assertRedirect(route('brands.create'));
});

test('users can update social post with media', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    // Create actual media records
    $media1 = \App\Models\Media::factory()->forBrand($brand)->create();
    $media2 = \App\Models\Media::factory()->forBrand($brand)->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->put(route('social-posts.update', $socialPost), [
        'content' => 'Updated content!',
        'hashtags' => ['updated'],
        'media' => [
            ['id' => $media1->id],
            ['id' => $media2->id],
        ],
    ]);

    $response->assertRedirect();
    $socialPost->refresh();
    expect($socialPost->media)->toBe([$media1->id, $media2->id]);
});

test('publish requires platform to be connected', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')->andReturn(false);
    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('social-posts.publish', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('publish dispatches job when platform is connected', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')->andReturn(true);
    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('social-posts.publish', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\PublishSocialPost::class);
});

test('cannot publish a published post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->published()->create();

    setupUserWithSocialPermissions($user);

    $response = $this->actingAs($user)->post(route('social-posts.publish', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'This post cannot be published.');
});

test('publishNow requires platform to be connected', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')->andReturn(false);
    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('social-posts.publish-now', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('publishNow returns error on failure', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create([
        'failure_reason' => 'API Error',
    ]);

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')->andReturn(true);

    $publishingService = Mockery::mock(\App\Services\SocialPublishing\SocialPublishingService::class);
    $publishingService->shouldReceive('publishAndUpdateStatus')->andReturn(false);

    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);
    app()->instance(\App\Services\SocialPublishing\SocialPublishingService::class, $publishingService);

    $response = $this->actingAs($user)->post(route('social-posts.publish-now', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('publishNow returns success on successful publish', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->draft()->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')->andReturn(true);

    $publishingService = Mockery::mock(\App\Services\SocialPublishing\SocialPublishingService::class);
    $publishingService->shouldReceive('publishAndUpdateStatus')->andReturn(true);

    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);
    app()->instance(\App\Services\SocialPublishing\SocialPublishingService::class, $publishingService);

    $response = $this->actingAs($user)->post(route('social-posts.publish-now', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('retry requires platform to be connected', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->failed()->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')->andReturn(false);
    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('social-posts.retry', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('retry dispatches job when platform is connected', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->failed()->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')->andReturn(true);
    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('social-posts.retry', $socialPost));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\PublishSocialPost::class);
});

test('bulk publish now requires all platforms to be connected', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post1 = SocialPost::factory()->forBrand($brand)->draft()->forPlatform(SocialPlatform::Instagram)->create();
    $post2 = SocialPost::factory()->forBrand($brand)->draft()->forPlatform(SocialPlatform::Facebook)->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')
        ->with(Mockery::type(\App\Models\Brand::class), 'instagram')
        ->andReturn(true);
    $tokenManager->shouldReceive('isConnected')
        ->with(Mockery::type(\App\Models\Brand::class), 'facebook')
        ->andReturn(false);
    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('social-posts.bulk-publish-now'), [
        'social_post_ids' => [$post1->id, $post2->id],
        'interval_minutes' => 15,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('bulk publish now dispatches jobs when all platforms connected', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post1 = SocialPost::factory()->forBrand($brand)->draft()->forPlatform(SocialPlatform::Instagram)->create();
    $post2 = SocialPost::factory()->forBrand($brand)->draft()->forPlatform(SocialPlatform::Instagram)->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('isConnected')->andReturn(true);
    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('social-posts.bulk-publish-now'), [
        'social_post_ids' => [$post1->id, $post2->id],
        'interval_minutes' => 15,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', '2 posts queued for publishing!');
    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\PublishSocialPost::class, 2);
});

test('connected platforms list is returned in index', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    setupUserWithSocialPermissions($user);

    $tokenManager = Mockery::mock(\App\Services\SocialPublishing\TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->with(Mockery::type(\App\Models\Brand::class), 'instagram')
        ->andReturn(['access_token' => 'test']);
    $tokenManager->shouldReceive('getCredentials')
        ->with(Mockery::type(\App\Models\Brand::class), 'facebook')
        ->andReturn(['access_token' => 'test', 'page_id' => '123']);
    $tokenManager->shouldReceive('getCredentials')
        ->with(Mockery::type(\App\Models\Brand::class), 'linkedin')
        ->andReturn(null);
    $tokenManager->shouldReceive('getCredentials')
        ->with(Mockery::type(\App\Models\Brand::class), 'pinterest')
        ->andReturn(['access_token' => 'test']); // No board_id, should be filtered
    $tokenManager->shouldReceive('getCredentials')
        ->with(Mockery::type(\App\Models\Brand::class), 'tiktok')
        ->andReturn(null);
    app()->instance(\App\Services\SocialPublishing\TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->get(route('social-posts.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
        ->has('connectedPlatforms')
        ->where('connectedPlatforms', ['instagram', 'facebook'])
    );
});
