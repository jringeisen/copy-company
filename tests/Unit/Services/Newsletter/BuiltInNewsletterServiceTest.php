<?php

namespace Tests\Unit\Services\Newsletter;

use App\Enums\NewsletterSendStatus;
use App\Jobs\SendNewsletterToSubscriber;
use App\Models\Account;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Newsletter\BuiltInNewsletterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BuiltInNewsletterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BuiltInNewsletterService $service;

    protected Brand $brand;

    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $account = Account::factory()->create();
        $account->users()->attach($user->id, ['role' => 'admin']);
        $this->brand = Brand::factory()->forAccount($account)->create();
        $this->post = Post::factory()->forBrand($this->brand)->published()->create();

        $this->service = new BuiltInNewsletterService;
    }

    public function test_send_creates_newsletter_send_record(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->forBrand($this->brand)->confirmed()->create();

        $newsletterSend = $this->service->send(
            $this->brand,
            $this->post,
            'Test Subject',
            'Preview text'
        );

        $this->assertInstanceOf(NewsletterSend::class, $newsletterSend);
        $this->assertEquals($this->brand->id, $newsletterSend->brand_id);
        $this->assertEquals($this->post->id, $newsletterSend->post_id);
        $this->assertEquals('Test Subject', $newsletterSend->subject_line);
        $this->assertEquals('Preview text', $newsletterSend->preview_text);
        $this->assertEquals(NewsletterSendStatus::Sending, $newsletterSend->status);
        $this->assertEquals(1, $newsletterSend->total_recipients);
        $this->assertEquals(0, $newsletterSend->sent_count);
        $this->assertEquals(0, $newsletterSend->failed_count);
        $this->assertNotNull($newsletterSend->sent_at);
    }

    public function test_send_dispatches_job_for_each_confirmed_subscriber(): void
    {
        Queue::fake();

        Subscriber::factory()->count(3)->forBrand($this->brand)->confirmed()->create();

        $newsletterSend = $this->service->send(
            $this->brand,
            $this->post,
            'Test Subject'
        );

        Queue::assertPushed(SendNewsletterToSubscriber::class, 3);
    }

    public function test_send_does_not_dispatch_jobs_for_pending_subscribers(): void
    {
        Queue::fake();

        Subscriber::factory()->count(2)->forBrand($this->brand)->confirmed()->create();
        Subscriber::factory()->count(2)->forBrand($this->brand)->pending()->create();

        $newsletterSend = $this->service->send(
            $this->brand,
            $this->post,
            'Test Subject'
        );

        $this->assertEquals(2, $newsletterSend->total_recipients);
        Queue::assertPushed(SendNewsletterToSubscriber::class, 2);
    }

    public function test_send_does_not_dispatch_jobs_for_unsubscribed_subscribers(): void
    {
        Queue::fake();

        Subscriber::factory()->count(2)->forBrand($this->brand)->confirmed()->create();
        Subscriber::factory()->count(1)->forBrand($this->brand)->unsubscribed()->create();

        $newsletterSend = $this->service->send(
            $this->brand,
            $this->post,
            'Test Subject'
        );

        $this->assertEquals(2, $newsletterSend->total_recipients);
        Queue::assertPushed(SendNewsletterToSubscriber::class, 2);
    }

    public function test_send_with_no_subscribers_creates_record_with_zero_recipients(): void
    {
        Queue::fake();

        $newsletterSend = $this->service->send(
            $this->brand,
            $this->post,
            'Test Subject'
        );

        $this->assertEquals(0, $newsletterSend->total_recipients);
        Queue::assertNotPushed(SendNewsletterToSubscriber::class);
    }

    public function test_send_without_preview_text(): void
    {
        Queue::fake();

        $subscriber = Subscriber::factory()->forBrand($this->brand)->confirmed()->create();

        $newsletterSend = $this->service->send(
            $this->brand,
            $this->post,
            'Test Subject'
        );

        $this->assertNull($newsletterSend->preview_text);
    }

    public function test_get_subscriber_count_returns_confirmed_subscribers_only(): void
    {
        Subscriber::factory()->count(3)->forBrand($this->brand)->confirmed()->create();
        Subscriber::factory()->count(2)->forBrand($this->brand)->pending()->create();
        Subscriber::factory()->count(1)->forBrand($this->brand)->unsubscribed()->create();

        $count = $this->service->getSubscriberCount($this->brand);

        $this->assertEquals(3, $count);
    }

    public function test_get_subscriber_count_returns_zero_for_brand_with_no_subscribers(): void
    {
        $count = $this->service->getSubscriberCount($this->brand);

        $this->assertEquals(0, $count);
    }

    public function test_get_subscriber_count_does_not_count_other_brand_subscribers(): void
    {
        Subscriber::factory()->count(2)->forBrand($this->brand)->confirmed()->create();

        $otherBrand = Brand::factory()->forAccount($this->brand->account)->create();
        Subscriber::factory()->count(5)->forBrand($otherBrand)->confirmed()->create();

        $count = $this->service->getSubscriberCount($this->brand);

        $this->assertEquals(2, $count);
    }

    public function test_send_only_sends_to_brand_subscribers(): void
    {
        Queue::fake();

        Subscriber::factory()->count(2)->forBrand($this->brand)->confirmed()->create();

        $otherBrand = Brand::factory()->forAccount($this->brand->account)->create();
        Subscriber::factory()->count(3)->forBrand($otherBrand)->confirmed()->create();

        $newsletterSend = $this->service->send(
            $this->brand,
            $this->post,
            'Test Subject'
        );

        $this->assertEquals(2, $newsletterSend->total_recipients);
        Queue::assertPushed(SendNewsletterToSubscriber::class, 2);
    }
}
