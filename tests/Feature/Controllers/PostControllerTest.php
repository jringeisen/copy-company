<?php

use App\Enums\PostStatus;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create all permissions needed for post operations
    Permission::findOrCreate('posts.create', 'web');
    Permission::findOrCreate('posts.update', 'web');
    Permission::findOrCreate('posts.delete', 'web');
    Permission::findOrCreate('posts.publish', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'posts.create',
        'posts.update',
        'posts.delete',
        'posts.publish',
    ]);
});

function setupUserWithPermissions(User $user): void
{
    $account = $user->accounts()->first();
    if ($account) {
        setPermissionsTeamId($account->id);
        $user->assignRole('admin');
    }
}

test('guests cannot access posts index', function () {
    $response = $this->get(route('posts.index'));

    $response->assertRedirect('/login');
});

test('users without brand are redirected to brand create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('posts.index'));

    $response->assertRedirect(route('brands.create'));
});

test('users with brand can view posts index', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Post::factory()->forBrand($brand)->count(3)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->get(route('posts.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Posts/Index')
        ->has('posts.data', 3)
        ->has('brand')
    );
});

test('users can view post create page', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->get(route('posts.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page->component('Posts/Create'));
});

test('users can create a post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('posts.store'), [
        'title' => 'My First Post',
        'content' => [['type' => 'paragraph', 'content' => 'This is the content.']],
        'content_html' => '<p>This is the content.</p>',
        'excerpt' => 'A short excerpt',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('posts', [
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'title' => 'My First Post',
        'status' => PostStatus::Draft->value,
    ]);
});

test('post title is required', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('posts.store'), [
        'content' => [['type' => 'paragraph', 'content' => 'Content']],
    ]);

    $response->assertSessionHasErrors('title');
});

test('users can view post edit page', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->get(route('posts.edit', $post));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Posts/Edit')
        ->has('post')
        ->has('brand')
    );
});

test('users cannot edit posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithPermissions($user);

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->get(route('posts.edit', $post));

    $response->assertForbidden();
});

test('users can update their posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->put(route('posts.update', $post), [
        'title' => 'Updated Title',
        'content' => [['type' => 'paragraph', 'content' => 'Updated content']],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
    ]);
});

test('users can delete their posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->delete(route('posts.destroy', $post));

    $response->assertRedirect(route('posts.index'));
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('users cannot delete posts from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithPermissions($user);

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $post = Post::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->delete(route('posts.destroy', $post));

    $response->assertForbidden();
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
});

test('users can publish a post immediately', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->draft()->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('posts.publish', $post), [
        'schedule_mode' => 'now',
        'publish_to_blog' => true,
        'send_as_newsletter' => false,
    ]);

    $response->assertRedirect(route('posts.index'));
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'status' => PostStatus::Published->value,
    ]);
});

test('users can schedule a post for later', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->draft()->create();

    setupUserWithPermissions($user);

    $scheduledAt = now()->addDays(3)->format('Y-m-d H:i:s');

    $response = $this->actingAs($user)->post(route('posts.publish', $post), [
        'schedule_mode' => 'scheduled',
        'scheduled_at' => $scheduledAt,
        'publish_to_blog' => true,
        'send_as_newsletter' => false,
    ]);

    $response->assertRedirect(route('posts.index'));
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'status' => PostStatus::Scheduled->value,
    ]);
});

test('users can bulk delete their posts', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $posts = Post::factory()->forBrand($brand)->count(3)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->delete(route('posts.bulk-destroy'), [
        'ids' => $posts->pluck('id')->toArray(),
    ]);

    $response->assertRedirect(route('posts.index'));
    foreach ($posts as $post) {
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
});

test('bulk delete rejects request containing posts from other brands', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $userPosts = Post::factory()->forBrand($brand)->count(2)->create();

    setupUserWithPermissions($user);

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherPosts = Post::factory()->forBrand($otherBrand)->count(2)->create();

    $allIds = $userPosts->pluck('id')->merge($otherPosts->pluck('id'))->toArray();

    $response = $this->actingAs($user)->delete(route('posts.bulk-destroy'), [
        'ids' => $allIds,
    ]);

    // Authorization fails if any post IDs don't belong to the user's brand
    $response->assertForbidden();

    // No posts should be deleted
    foreach ($userPosts as $post) {
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    foreach ($otherPosts as $post) {
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
});

test('bulk delete requires at least one post id', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->delete(route('posts.bulk-destroy'), [
        'ids' => [],
    ]);

    $response->assertSessionHasErrors('ids');
});

test('bulk delete rejects non-existent post ids', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupUserWithPermissions($user);

    $response = $this->actingAs($user)->delete(route('posts.bulk-destroy'), [
        'ids' => [99999],
    ]);

    // Authorization fails because none of the IDs belong to the user's brand
    $response->assertForbidden();
});
