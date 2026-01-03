<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\NewsletterSend;
use App\Models\Subscriber;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewsletterToSubscriber implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

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
            Mail::to($this->subscriber->email)
                ->send(new NewsletterMail($this->newsletterSend, $this->subscriber));

            $this->newsletterSend->increment('sent_count');

            Log::debug('Newsletter sent to subscriber', [
                'newsletter_send_id' => $this->newsletterSend->id,
                'subscriber_id' => $this->subscriber->id,
                'email' => $this->subscriber->email,
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
