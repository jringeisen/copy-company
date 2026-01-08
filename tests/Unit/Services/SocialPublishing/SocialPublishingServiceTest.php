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
            'platform' => SocialPlatform::Twitter,
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
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Draft,
        ]);

        $this->assertFalse($this->service->canPublish($socialPost));
    }

    public function test_can_publish_returns_true_when_connected(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Draft,
        ]);

        $this->assertTrue($this->service->canPublish($socialPost));
    }

    public function test_publish_and_update_status_sets_failed_on_error(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
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
        $this->tokenManager->storeCredentials($this->brand, 'twitter', [
            // Missing access_token
            'account_id' => '12345',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Twitter,
            'status' => SocialPostStatus::Draft,
        ]);

        $result = $this->service->publish($socialPost);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid or expired credentials', $result['error']);
    }
}
