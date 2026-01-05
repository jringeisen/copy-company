<?php

namespace App\Services;

use App\Enums\SubscriberStatus;
use App\Models\EmailEvent;
use Illuminate\Support\Facades\Log;

class SesEventProcessor
{
    /**
     * Process an SES event notification.
     */
    public function process(array $message): void
    {
        $eventType = $message['eventType'] ?? $message['notificationType'] ?? null;
        $messageId = $message['mail']['messageId'] ?? null;

        if (! $messageId) {
            Log::warning('SES event received without message ID', ['message' => $message]);

            return;
        }

        // Find the original sent event to get subscriber/newsletter context
        $sentEvent = EmailEvent::where('ses_message_id', $messageId)
            ->where('event_type', 'sent')
            ->first();

        match ($eventType) {
            'Bounce' => $this->handleBounce($message, $sentEvent),
            'Complaint' => $this->handleComplaint($message, $sentEvent),
            'Delivery' => $this->handleDelivery($message, $sentEvent),
            'Open' => $this->handleOpen($message, $sentEvent),
            'Click' => $this->handleClick($message, $sentEvent),
            default => Log::info('Unhandled SES event type', ['type' => $eventType]),
        };
    }

    /**
     * Handle a bounce event.
     */
    private function handleBounce(array $message, ?EmailEvent $sentEvent): void
    {
        $bounce = $message['bounce'];
        $bounceType = $bounce['bounceType']; // Permanent or Transient

        $this->createEvent('bounce', $message, $sentEvent);

        if ($sentEvent?->subscriber) {
            $subscriber = $sentEvent->subscriber;

            if ($bounceType === 'Permanent') {
                $subscriber->update([
                    'status' => SubscriberStatus::Bounced,
                    'bounce_type' => 'hard',
                    'last_bounce_at' => now(),
                ]);

                Log::info('Subscriber marked as hard bounced', [
                    'subscriber_id' => $subscriber->id,
                    'email' => $subscriber->email,
                ]);
            } else {
                $subscriber->increment('soft_bounce_count');
                $subscriber->update(['last_bounce_at' => now()]);

                // After 3 soft bounces, mark as bounced
                if ($subscriber->fresh()->soft_bounce_count >= 3) {
                    $subscriber->update([
                        'status' => SubscriberStatus::Bounced,
                        'bounce_type' => 'soft',
                    ]);

                    Log::info('Subscriber marked as soft bounced after 3 attempts', [
                        'subscriber_id' => $subscriber->id,
                        'email' => $subscriber->email,
                    ]);
                }
            }
        }

        // Update newsletter stats
        $sentEvent?->newsletterSend?->increment('failed_count');
    }

    /**
     * Handle a complaint event. This is critical for reputation.
     */
    private function handleComplaint(array $message, ?EmailEvent $sentEvent): void
    {
        $this->createEvent('complaint', $message, $sentEvent);

        // Immediately mark as complained - critical for reputation
        if ($sentEvent?->subscriber) {
            $sentEvent->subscriber->update([
                'status' => SubscriberStatus::Complained,
            ]);

            Log::warning('Subscriber complained - marked immediately', [
                'subscriber_id' => $sentEvent->subscriber->id,
                'email' => $sentEvent->subscriber->email,
            ]);
        }

        $sentEvent?->newsletterSend?->increment('unsubscribes');
    }

    /**
     * Handle a delivery confirmation event.
     */
    private function handleDelivery(array $message, ?EmailEvent $sentEvent): void
    {
        $this->createEvent('delivery', $message, $sentEvent);
    }

    /**
     * Handle an open tracking event.
     */
    private function handleOpen(array $message, ?EmailEvent $sentEvent): void
    {
        $messageId = $message['mail']['messageId'];

        // Check if this is a unique open (first open for this message)
        $existingOpen = EmailEvent::where('ses_message_id', $messageId)
            ->where('event_type', 'open')
            ->exists();

        $this->createEvent('open', $message, $sentEvent);

        $sentEvent?->newsletterSend?->increment('opens');

        if (! $existingOpen) {
            $sentEvent?->newsletterSend?->increment('unique_opens');
        }
    }

    /**
     * Handle a click tracking event.
     */
    private function handleClick(array $message, ?EmailEvent $sentEvent): void
    {
        $messageId = $message['mail']['messageId'];
        $linkUrl = $message['click']['link'] ?? null;

        // Check if this is a unique click (first click for this message)
        $existingClick = EmailEvent::where('ses_message_id', $messageId)
            ->where('event_type', 'click')
            ->exists();

        $this->createEvent('click', $message, $sentEvent, $linkUrl);

        $sentEvent?->newsletterSend?->increment('clicks');

        if (! $existingClick) {
            $sentEvent?->newsletterSend?->increment('unique_clicks');
        }
    }

    /**
     * Create an email event record.
     */
    private function createEvent(string $type, array $message, ?EmailEvent $sentEvent, ?string $linkUrl = null): void
    {
        EmailEvent::create([
            'subscriber_id' => $sentEvent?->subscriber_id,
            'newsletter_send_id' => $sentEvent?->newsletter_send_id,
            'ses_message_id' => $message['mail']['messageId'],
            'event_type' => $type,
            'event_data' => $message,
            'link_url' => $linkUrl,
            'event_at' => now(),
        ]);
    }
}
