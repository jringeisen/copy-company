<?php

namespace Tests\Feature\Jobs;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Jobs\ProcessScheduledSocialPosts;
use App\Jobs\PublishSocialPost;
use App\Models\Account;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessScheduledSocialPostsTest extends TestCase
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

    public function test_dispatches_publish_job_for_due_scheduled_posts(): void
    {
        Queue::fake();

        $duePost = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);

        $job = new ProcessScheduledSocialPosts;
        $job->handle();

        Queue::assertPushed(PublishSocialPost::class, function ($job) use ($duePost) {
            return $job->socialPost->id === $duePost->id;
        });
    }

    public function test_does_not_dispatch_for_future_scheduled_posts(): void
    {
        Queue::fake();

        SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now()->addHour(),
        ]);

        $job = new ProcessScheduledSocialPosts;
        $job->handle();

        Queue::assertNotPushed(PublishSocialPost::class);
    }

    public function test_does_not_dispatch_for_non_scheduled_posts(): void
    {
        Queue::fake();

        SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Draft,
        ]);

        SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Queued,
        ]);

        SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Published,
        ]);

        $job = new ProcessScheduledSocialPosts;
        $job->handle();

        Queue::assertNotPushed(PublishSocialPost::class);
    }

    public function test_dispatches_multiple_due_posts(): void
    {
        Queue::fake();

        $post1 = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now()->subMinutes(5),
        ]);

        $post2 = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now()->subMinutes(2),
        ]);

        $post3 = SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Instagram,
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now(),
        ]);

        $job = new ProcessScheduledSocialPosts;
        $job->handle();

        Queue::assertPushed(PublishSocialPost::class, 3);
    }

    public function test_handles_posts_from_multiple_brands(): void
    {
        Queue::fake();

        $otherAccount = Account::factory()->create();
        $otherBrand = Brand::factory()->forAccount($otherAccount)->create();

        SocialPost::factory()->create([
            'brand_id' => $this->brand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);

        SocialPost::factory()->create([
            'brand_id' => $otherBrand->id,
            'platform' => SocialPlatform::Facebook,
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);

        $job = new ProcessScheduledSocialPosts;
        $job->handle();

        Queue::assertPushed(PublishSocialPost::class, 2);
    }
}
