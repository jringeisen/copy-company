<?php

namespace App\Jobs;

use App\Enums\NewsletterSendStatus;
use App\Enums\SubscriberStatus;
use App\Models\NewsletterSend;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ProcessNewsletterSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public NewsletterSend $newsletterSend
    ) {}

    public function handle(): void
    {
        $newsletterSend = $this->newsletterSend;

        // Skip if already processing or sent
        if (in_array($newsletterSend->status, [NewsletterSendStatus::Sending, NewsletterSendStatus::Sent])) {
            return;
        }

        /** @var \App\Models\Brand $brand */
        $brand = $newsletterSend->brand;

        // Get confirmed subscribers count
        $subscriberCount = $brand->subscribers()
            ->where('status', SubscriberStatus::Confirmed)
            ->count();

        if ($subscriberCount === 0) {
            $newsletterSend->update([
                'status' => NewsletterSendStatus::Sent,
                'total_recipients' => 0,
                'sent_at' => now(),
            ]);

            Log::info('Newsletter send completed with no subscribers', [
                'newsletter_send_id' => $newsletterSend->id,
            ]);

            return;
        }

        // Update status to sending
        $newsletterSend->update([
            'status' => NewsletterSendStatus::Sending,
            'total_recipients' => $subscriberCount,
        ]);

        // Build jobs array by chunking subscribers
        $jobs = [];

        $brand->subscribers()
            ->where('status', SubscriberStatus::Confirmed)
            ->chunkById(100, function (Collection $subscribers) use (&$jobs, $newsletterSend): void {
                /** @var Subscriber $subscriber */
                foreach ($subscribers as $subscriber) {
                    $jobs[] = new SendNewsletterToSubscriber($newsletterSend, $subscriber);
                }
            });

        // Create and dispatch the batch
        $batch = Bus::batch($jobs)
            ->name("Newsletter Send #{$newsletterSend->id}")
            ->allowFailures()
            ->finally(function () use ($newsletterSend) {
                // Refresh to get latest counts
                $newsletterSend->refresh();

                $newsletterSend->update([
                    'status' => NewsletterSendStatus::Sent,
                    'sent_at' => now(),
                ]);

                Log::info('Newsletter send completed', [
                    'newsletter_send_id' => $newsletterSend->id,
                    'total' => $newsletterSend->total_recipients,
                    'sent' => $newsletterSend->sent_count,
                    'failed' => $newsletterSend->failed_count,
                ]);
            })
            ->onQueue('newsletters')
            ->dispatch();

        // Store batch ID for tracking
        $newsletterSend->update(['batch_id' => $batch->id]);

        Log::info('Newsletter batch dispatched', [
            'newsletter_send_id' => $newsletterSend->id,
            'batch_id' => $batch->id,
            'job_count' => count($jobs),
        ]);
    }
}
