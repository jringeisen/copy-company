<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
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
            $sentMessage = Mail::to($this->subscriber->email)
                ->send(new NewsletterMail($this->newsletterSend, $this->subscriber));

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
            if ($account = $this->newsletterSend->brand?->account) {
                EmailUsage::recordEmailSent($account);
            }

            Log::debug('Newsletter sent to subscriber', [
                'newsletter_send_id' => $this->newsletterSend->id,
                'subscriber_id' => $this->subscriber->id,
                'email' => $this->subscriber->email,
                'ses_message_id' => $messageId,
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
}
