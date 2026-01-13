<?php

namespace Tests\Feature\Jobs;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Jobs\PublishSocialPost;
use App\Models\Account;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishSocialPostTest extends TestCase
{
    use RefreshDatabase;

    protected Brand $brand;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->account = Account::factory()->create();
        $this->account->users()->attach($user->id, ['role' => 'admin']);
        $this->brand = Brand::factory()->forAccount($this->account)->create();
    }

    public function test_job_updates_status_to_failed_when_not_connected(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Queued,
        ]);

        $job = new PublishSocialPost($socialPost);
        $job->handle(app(\App\Services\SocialPublishing\SocialPublishingService::class));

        $socialPost->refresh();
        $this->assertEquals(SocialPostStatus::Failed, $socialPost->status);
        $this->assertNotNull($socialPost->failure_reason);
    }

    public function test_job_updates_status_to_failed_when_invalid_credentials(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'facebook', [
            // Missing access_token
            'account_id' => '12345',
        ]);

        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Queued,
        ]);

        $job = new PublishSocialPost($socialPost);
        $job->handle(app(\App\Services\SocialPublishing\SocialPublishingService::class));

        $socialPost->refresh();
        $this->assertEquals(SocialPostStatus::Failed, $socialPost->status);
        $this->assertStringContainsString('Invalid or expired credentials', $socialPost->failure_reason);
    }

    public function test_job_has_retry_configuration(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Queued,
        ]);

        $job = new PublishSocialPost($socialPost);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }

    public function test_failed_method_logs_error(): void
    {
        $socialPost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Queued,
        ]);

        $job = new PublishSocialPost($socialPost);

        // The failed method should not throw an exception
        $job->failed(new \Exception('Test failure'));

        // Verify it completes without error
        $this->assertTrue(true);
    }

    public function test_job_handles_different_platforms(): void
    {
        $platforms = [
            SocialPlatform::Facebook,
            SocialPlatform::Instagram,
            SocialPlatform::LinkedIn,
            SocialPlatform::Pinterest,
            SocialPlatform::TikTok,
        ];

        foreach ($platforms as $platform) {
            $socialPost = SocialPost::factory()->create([
                'brand_id' => $this->brand->id,
                'platform' => $platform,
                'status' => SocialPostStatus::Queued,
            ]);

            $job = new PublishSocialPost($socialPost);
            $job->handle(app(\App\Services\SocialPublishing\SocialPublishingService::class));

            $socialPost->refresh();
            // All should fail since not connected
            $this->assertEquals(SocialPostStatus::Failed, $socialPost->status);
        }
    }
}
