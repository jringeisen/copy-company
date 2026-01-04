<?php

namespace Tests\Feature\Controllers;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Jobs\PublishSocialPost;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SocialPostPublishingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->brand = Brand::factory()->create(['user_id' => $this->user->id]);
        $this->user->update(['current_brand_id' => $this->brand->id]);
    }

    public function test_publish_requires_authentication(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'status' => SocialPostStatus::Draft,
        ]);

        $response = $this->post("/social-posts/{$socialPost->id}/publish");

        $response->assertRedirect('/login');
    }

    public function test_publish_fails_when_platform_not_connected(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/publish");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_publish_dispatches_job_when_connected(): void
    {
        Queue::fake();

        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/publish");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Queue::assertPushed(PublishSocialPost::class, function ($job) use ($socialPost) {
            return $job->socialPost->id === $socialPost->id;
        });
    }

    public function test_publish_fails_for_already_published_post(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Published,
        ]);

        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/publish");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This post cannot be published.');
    }

    public function test_retry_works_for_failed_posts(): void
    {
        Queue::fake();

        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Failed,
            'failure_reason' => 'Previous error',
        ]);

        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/retry");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $socialPost->refresh();
        $this->assertEquals(SocialPostStatus::Queued, $socialPost->status);
        $this->assertNull($socialPost->failure_reason);

        Queue::assertPushed(PublishSocialPost::class);
    }

    public function test_retry_fails_for_non_failed_posts(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/retry");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Only failed posts can be retried.');
    }

    public function test_users_cannot_publish_other_brands_posts(): void
    {
        $otherUser = User::factory()->create();
        $otherBrand = Brand::factory()->create(['user_id' => $otherUser->id]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $otherBrand->id,
            'status' => SocialPostStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/publish");

        $response->assertForbidden();
    }

    public function test_publish_now_publishes_synchronously(): void
    {
        // Set config values for test
        config([
            'services.twitter.client_id' => 'test_client_id',
            'services.twitter.client_secret' => 'test_client_secret',
        ]);

        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Draft,
            'content' => 'Test tweet content',
        ]);

        // This will fail because we don't have real API credentials,
        // but it tests that the synchronous path is working
        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/publish-now");

        $response->assertRedirect();

        $socialPost->refresh();
        // Will be Failed because no real API credentials
        $this->assertEquals(SocialPostStatus::Failed, $socialPost->status);
    }

    public function test_queued_posts_can_be_published(): void
    {
        Queue::fake();

        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Queued,
        ]);

        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/publish");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Queue::assertPushed(PublishSocialPost::class);
    }

    public function test_scheduled_posts_can_be_published(): void
    {
        Queue::fake();

        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)->post("/social-posts/{$socialPost->id}/publish");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Queue::assertPushed(PublishSocialPost::class);
    }
}
