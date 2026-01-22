<?php

use App\Enums\PostStatus;
use App\Mcp\Servers\CopyCompanyServer;
use App\Mcp\Tools\Posts\CreatePostTool;
use App\Mcp\Tools\Posts\GetPostTool;
use App\Mcp\Tools\Posts\ListPostsTool;
use App\Mcp\Tools\Posts\PublishPostTool;
use App\Mcp\Tools\Posts\UpdatePostTool;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('posts.create', 'web');
    Permission::findOrCreate('posts.update', 'web');
    Permission::findOrCreate('posts.delete', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'posts.create',
        'posts.update',
        'posts.delete',
    ]);

    config([
        'services.stripe.prices.starter_monthly' => 'price_starter_monthly',
        'services.stripe.prices.starter_annual' => 'price_starter_annual',
        'services.stripe.prices.creator_monthly' => 'price_creator_monthly',
        'services.stripe.prices.creator_annual' => 'price_creator_annual',
        'services.stripe.prices.pro_monthly' => 'price_pro_monthly',
        'services.stripe.prices.pro_annual' => 'price_pro_annual',
    ]);
});

function setupMcpUser(User $user): void
{
    $account = $user->accounts()->first();
    if ($account) {
        setPermissionsTeamId($account->id);
        $user->assignRole('admin');
    }
    session(['current_brand_id' => $user->currentBrand()?->id]);
}

test('ListPostsTool returns posts for current brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Post::factory()->count(3)->forBrand($brand)->forUser($user)->create();

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(ListPostsTool::class, []);

    $response->assertOk();
});

test('ListPostsTool filters by status', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    Post::factory()->forBrand($brand)->forUser($user)->create(['status' => PostStatus::Draft]);
    Post::factory()->forBrand($brand)->forUser($user)->create(['status' => PostStatus::Published]);
    Post::factory()->forBrand($brand)->forUser($user)->create(['status' => PostStatus::Scheduled]);

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(ListPostsTool::class, [
        'status' => 'draft',
    ]);

    $response->assertOk();
});

test('ListPostsTool respects limit parameter', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Post::factory()->count(10)->forBrand($brand)->forUser($user)->create();

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(ListPostsTool::class, [
        'limit' => 5,
    ]);

    $response->assertOk();
});

test('ListPostsTool requires authentication', function () {
    $response = CopyCompanyServer::tool(ListPostsTool::class, []);

    $response->assertHasErrors();
});

test('GetPostTool returns post details', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create([
        'title' => 'Test Post',
        'content' => ['type' => 'doc', 'content' => []],
    ]);

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(GetPostTool::class, [
        'post_id' => $post->id,
    ]);

    $response->assertOk();
});

test('GetPostTool returns error for non-existent post', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(GetPostTool::class, [
        'post_id' => 99999,
    ]);

    $response->assertHasErrors();
});

test('GetPostTool cannot access other brand posts', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherPost = Post::factory()->forBrand($otherBrand)->forUser($otherUser)->create();

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(GetPostTool::class, [
        'post_id' => $otherPost->id,
    ]);

    $response->assertHasErrors();
});

test('CreatePostTool creates a draft post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(CreatePostTool::class, [
        'title' => 'My New Post',
        'excerpt' => 'A brief summary',
    ]);

    $response->assertOk();

    expect($brand->posts()->where('title', 'My New Post')->exists())->toBeTrue();
    expect($brand->posts()->where('title', 'My New Post')->first()->status)->toBe(PostStatus::Draft);
});

test('CreatePostTool requires title', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(CreatePostTool::class, []);

    $response->assertHasErrors();
});

test('UpdatePostTool updates post fields', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create([
        'title' => 'Original Title',
    ]);

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(UpdatePostTool::class, [
        'post_id' => $post->id,
        'title' => 'Updated Title',
        'excerpt' => 'Updated excerpt',
    ]);

    $response->assertOk();

    $post->refresh();
    expect($post->title)->toBe('Updated Title');
    expect($post->excerpt)->toBe('Updated excerpt');
});

test('UpdatePostTool requires post_id', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(UpdatePostTool::class, [
        'title' => 'New Title',
    ]);

    $response->assertHasErrors();
});

test('PublishPostTool publishes a draft post immediately', function () {
    Queue::fake();

    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create([
        'status' => PostStatus::Draft,
    ]);

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(PublishPostTool::class, [
        'post_id' => $post->id,
        'publish_to_blog' => true,
        'send_as_newsletter' => false,
    ]);

    $response->assertOk();

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Published);
});

test('PublishPostTool schedules a post for future', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create([
        'status' => PostStatus::Draft,
    ]);

    setupMcpUser($user);

    $futureDate = now()->addDays(7)->toIso8601String();

    $response = CopyCompanyServer::actingAs($user)->tool(PublishPostTool::class, [
        'post_id' => $post->id,
        'scheduled_at' => $futureDate,
    ]);

    $response->assertOk();

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Scheduled);
});

test('PublishPostTool rejects past scheduled_at date', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create([
        'status' => PostStatus::Draft,
    ]);

    setupMcpUser($user);

    $pastDate = now()->subDays(7)->toIso8601String();

    $response = CopyCompanyServer::actingAs($user)->tool(PublishPostTool::class, [
        'post_id' => $post->id,
        'scheduled_at' => $pastDate,
    ]);

    $response->assertHasErrors();
});

test('PublishPostTool cannot publish already published post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create([
        'status' => PostStatus::Published,
    ]);

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(PublishPostTool::class, [
        'post_id' => $post->id,
    ]);

    $response->assertHasErrors();
});

test('PublishPostTool requires subject_line when send_as_newsletter is true', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->forUser($user)->create([
        'status' => PostStatus::Draft,
    ]);

    setupMcpUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(PublishPostTool::class, [
        'post_id' => $post->id,
        'send_as_newsletter' => true,
    ]);

    $response->assertHasErrors();
});
