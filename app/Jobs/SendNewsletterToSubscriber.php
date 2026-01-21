<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\Brand;
use App\Models\EmailEvent;
use App\Models\EmailUsage;
use App\Models\NewsletterSend;
use App\Models\Subscriber;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewsletterToSubscriber implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('ses-sending'),
        ];
    }

    public function __construct(
        public NewsletterSend $newsletterSend,
        public Subscriber $subscriber
    ) {}

    public function handle(): void
    {
        // Check if batch was cancelled
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            /** @var Brand $brand */
            $brand = $this->newsletterSend->brand;

            // Determine if this email should use the dedicated IP
            $useDedicatedIp = $brand->shouldUseDedicatedIp();
            $configSet = $useDedicatedIp
                ? $brand->getSesConfigurationSet()
                : config('services.ses.configuration_set', 'shared-pool');

            $mailable = new NewsletterMail($this->newsletterSend, $this->subscriber);

            // Add SES configuration set header
            if ($configSet) {
                $mailable->withSymfonyMessage(function ($message) use ($configSet) {
                    $message->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', $configSet);
                });
            }

            $sentMessage = Mail::to($this->subscriber->email)->send($mailable);

            // Store the SES message ID for tracking correlation
            $messageId = $sentMessage?->getMessageId();
            if ($messageId) {
                EmailEvent::create([
                    'subscriber_id' => $this->subscriber->id,
                    'newsletter_send_id' => $this->newsletterSend->id,
                    'ses_message_id' => $messageId,
                    'event_type' => 'sent',
                    'event_at' => now(),
                ]);
            }

            $this->newsletterSend->increment('sent_count');

            // Record email usage for metered billing
            if ($account = $brand->account) {
                EmailUsage::recordEmailSent($account);
            }

            // Track warmup sends if in warmup period
            if ($brand->isInWarmupPeriod()) {
                $this->trackWarmupSend($brand, $useDedicatedIp);
            }

            Log::debug('Newsletter sent to subscriber', [
                'newsletter_send_id' => $this->newsletterSend->id,
                'subscriber_id' => $this->subscriber->id,
                'email' => $this->subscriber->email,
                'ses_message_id' => $messageId,
                'config_set' => $configSet,
                'used_dedicated_ip' => $useDedicatedIp,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send newsletter to subscriber', [
                'newsletter_send_id' => $this->newsletterSend->id,
                'subscriber_id' => $this->subscriber->id,
                'email' => $this->subscriber->email,
                'error' => $e->getMessage(),
            ]);

            $this->newsletterSend->increment('failed_count');

            // Re-throw to let the batch know this job failed
            throw $e;
        }
    }

    /**
     * Track warmup sending stats.
     */
    private function trackWarmupSend(Brand $brand, bool $usedDedicatedIp): void
    {
        $stats = $brand->warmup_daily_stats ?? [];
        $today = now()->toDateString();

        if (! isset($stats[$today])) {
            $stats[$today] = ['dedicated' => 0, 'shared' => 0];
        }

        if ($usedDedicatedIp) {
            $stats[$today]['dedicated']++;
        } else {
            $stats[$today]['shared']++;
        }

        $brand->updateQuietly([
            'warmup_daily_stats' => $stats,
            'last_warmup_send_at' => now(),
        ]);
    }
}
