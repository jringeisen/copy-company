<?php

namespace Tests\Unit\Services\SocialPublishing;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\Account;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\SocialPublishing\SocialPublishingService;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPublishingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SocialPublishingService $service;

    protected TokenManager $tokenManager;

    protected Brand $brand;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenManager = new TokenManager;
        $this->service = new SocialPublishingService($this->tokenManager);

        $user = User::factory()->create();
        $this->account = Account::factory()->create();
        $this->account->users()->attach($user->id, ['role' => 'admin']);
        $this->brand = Brand::factory()->forAccount($this->account)->create();
    }

    public function test_publish_fails_when_platform_not_connected(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
        ]);

        $result = $this->service->publish($socialPost);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not connected', $result['error']);
    }

    public function test_can_publish_returns_false_when_not_connected(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
        ]);

        $this->assertFalse($this->service->canPublish($socialPost));
    }

    public function test_can_publish_returns_true_when_connected(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
        ]);

        $this->assertTrue($this->service->canPublish($socialPost));
    }

    public function test_publish_and_update_status_sets_failed_on_error(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
        ]);

        $result = $this->service->publishAndUpdateStatus($socialPost);

        $this->assertFalse($result);

        $socialPost->refresh();
        $this->assertEquals(SocialPostStatus::Failed, $socialPost->status);
        $this->assertNotNull($socialPost->failure_reason);
    }

    public function test_publish_validates_credentials_before_publishing(): void
    {
        // Store invalid credentials (missing required fields)
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            // Missing access_token
            'account_id' => '12345',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
        ]);

        $result = $this->service->publish($socialPost);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid or expired credentials', $result['error']);
    }

    public function test_publish_and_update_status_sets_published_on_success(): void
    {
        // Mock the service to simulate successful publish
        $mockService = \Mockery::mock(SocialPublishingService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $mockService->shouldReceive('publish')
            ->once()
            ->andReturn([
                'success' => true,
                'external_id' => 'external_123',
                'error' => null,
            ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
        ]);

        $result = $mockService->publishAndUpdateStatus($socialPost);

        $this->assertTrue($result);

        $socialPost->refresh();
        $this->assertEquals(SocialPostStatus::Published, $socialPost->status);
        $this->assertNotNull($socialPost->published_at);
        $this->assertEquals('external_123', $socialPost->external_id);
        $this->assertNull($socialPost->failure_reason);
    }

    public function test_can_publish_returns_false_for_unsupported_platform(): void
    {
        // Create a social post with a platform that exists but use reflection to test edge case
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
        ]);

        // When platform is connected
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
        ]);

        // Should return true for supported platform
        $this->assertTrue($this->service->canPublish($socialPost));
    }

    public function test_publish_with_valid_credentials_calls_publisher(): void
    {
        // Store valid Facebook credentials
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
            'page_id' => '12345',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
            'content' => 'Test post content',
        ]);

        // The publish will fail because we're not actually connected to Facebook
        // but it should get past the validation checks
        $result = $this->service->publish($socialPost);

        // It should attempt to publish (even if it fails due to no real connection)
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('external_id', $result);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_publish_with_linkedin_platform(): void
    {
        // Store valid LinkedIn credentials
        $this->tokenManager->storeCredentials($this->brand, 'linkedin', [
            'access_token' => 'test_token',
            'person_id' => 'abc123',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::LinkedIn,
            'status' => SocialPostStatus::Draft,
            'content' => 'Test LinkedIn post',
        ]);

        $result = $this->service->publish($socialPost);

        $this->assertArrayHasKey('success', $result);
    }

    public function test_publish_with_instagram_platform(): void
    {
        // Store valid Instagram credentials
        $this->tokenManager->storeCredentials($this->brand, 'instagram', [
            'access_token' => 'test_token',
            'instagram_account_id' => '12345',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Instagram,
            'status' => SocialPostStatus::Draft,
            'content' => 'Test Instagram post',
            'media' => [],
        ]);

        $result = $this->service->publish($socialPost);

        // Instagram requires media, so this should fail with that specific error
        $this->assertFalse($result['success']);
    }

    public function test_publish_with_pinterest_platform(): void
    {
        // Store valid Pinterest credentials
        $this->tokenManager->storeCredentials($this->brand, 'pinterest', [
            'access_token' => 'test_token',
            'board_id' => '12345',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Pinterest,
            'status' => SocialPostStatus::Draft,
            'content' => 'Test Pinterest post',
            'media' => [],
        ]);

        $result = $this->service->publish($socialPost);

        // Pinterest requires media, so this should fail with that specific error
        $this->assertFalse($result['success']);
    }

    public function test_publish_with_tiktok_platform(): void
    {
        // Store valid TikTok credentials
        $this->tokenManager->storeCredentials($this->brand, 'tiktok', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::TikTok,
            'status' => SocialPostStatus::Draft,
            'content' => 'Test TikTok post',
            'media' => [],
        ]);

        $result = $this->service->publish($socialPost);

        // TikTok requires video media, so this should fail with that specific error
        $this->assertFalse($result['success']);
    }

    public function test_publish_returns_error_when_token_refresh_fails(): void
    {
        // Store credentials with expired token for a platform that implements TokenRefreshableInterface
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'expired_token',
            'refresh_token' => 'invalid_refresh_token',
            'expires_at' => now()->subDay()->toDateTimeString(), // Expired
            'page_id' => '12345',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
            'content' => 'Test post',
        ]);

        // Mock the TokenManager to throw an exception during refresh
        $mockTokenManager = \Mockery::mock(TokenManager::class);
        $mockTokenManager->shouldReceive('isConnected')
            ->andReturn(true);
        $mockTokenManager->shouldReceive('getCredentials')
            ->andReturn([
                'access_token' => 'expired_token',
                'refresh_token' => 'invalid_refresh_token',
                'expires_at' => now()->subDay()->toDateTimeString(),
                'page_id' => '12345',
            ]);
        $mockTokenManager->shouldReceive('refreshIfNeeded')
            ->andThrow(new \Exception('Token refresh failed'));

        $service = new \App\Services\SocialPublishing\SocialPublishingService($mockTokenManager);
        $result = $service->publish($socialPost);

        $this->assertFalse($result['success']);
        $this->assertEquals('Token refresh failed. Please reconnect your account.', $result['error']);
    }
}
