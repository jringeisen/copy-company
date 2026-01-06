<?php

namespace App\Console\Commands;

use App\Models\EmailEvent;
use App\Services\SesEventProcessor;
use Illuminate\Console\Command;

class SimulateSesWebhook extends Command
{
    protected $signature = 'ses:simulate
                            {event : Event type (bounce, complaint, delivery, open, click)}
                            {--message-id= : SES message ID to simulate event for}
                            {--email= : Email address for the event}';

    protected $description = 'Simulate SES webhook events for local testing';

    public function handle(SesEventProcessor $processor): int
    {
        $eventType = $this->argument('event');
        $messageId = $this->option('message-id');
        $email = $this->option('email') ?? 'test@example.com';

        // If no message ID provided, try to find the most recent sent event
        if (! $messageId) {
            $lastSent = EmailEvent::where('event_type', 'sent')
                ->latest()
                ->first();

            if ($lastSent) {
                $messageId = $lastSent->ses_message_id;
                $this->info("Using most recent message ID: {$messageId}");
            } else {
                $messageId = 'test-message-'.uniqid();
                $this->warn("No sent events found. Using generated ID: {$messageId}");
            }
        }

        $message = $this->buildMessage($eventType, $messageId, $email);

        if (! $message) {
            $this->error("Unknown event type: {$eventType}");
            $this->line('Valid types: bounce, complaint, delivery, open, click');

            return Command::FAILURE;
        }

        $this->info("Simulating {$eventType} event...");
        $processor->process($message);
        $this->info('Event processed successfully!');

        // Show the created event
        $event = EmailEvent::where('ses_message_id', $messageId)
            ->where('event_type', $eventType)
            ->latest()
            ->first();

        if ($event) {
            $this->table(
                ['ID', 'Type', 'Subscriber ID', 'Newsletter Send ID', 'Created'],
                [[$event->id, $event->event_type, $event->subscriber_id ?? 'N/A', $event->newsletter_send_id ?? 'N/A', $event->created_at]]
            );
        }

        return Command::SUCCESS;
    }

    private function buildMessage(string $eventType, string $messageId, string $email): ?array
    {
        $baseMessage = [
            'mail' => [
                'messageId' => $messageId,
                'destination' => [$email],
                'source' => 'noreply@wordsmith.com',
                'timestamp' => now()->toISOString(),
            ],
        ];

        return match ($eventType) {
            'bounce' => array_merge($baseMessage, [
                'eventType' => 'Bounce',
                'bounce' => [
                    'bounceType' => 'Permanent',
                    'bounceSubType' => 'General',
                    'bouncedRecipients' => [
                        ['emailAddress' => $email],
                    ],
                    'timestamp' => now()->toISOString(),
                ],
            ]),
            'soft-bounce' => array_merge($baseMessage, [
                'eventType' => 'Bounce',
                'bounce' => [
                    'bounceType' => 'Transient',
                    'bounceSubType' => 'MailboxFull',
                    'bouncedRecipients' => [
                        ['emailAddress' => $email],
                    ],
                    'timestamp' => now()->toISOString(),
                ],
            ]),
            'complaint' => array_merge($baseMessage, [
                'eventType' => 'Complaint',
                'complaint' => [
                    'complainedRecipients' => [
                        ['emailAddress' => $email],
                    ],
                    'complaintFeedbackType' => 'abuse',
                    'timestamp' => now()->toISOString(),
                ],
            ]),
            'delivery' => array_merge($baseMessage, [
                'eventType' => 'Delivery',
                'delivery' => [
                    'recipients' => [$email],
                    'timestamp' => now()->toISOString(),
                    'smtpResponse' => '250 OK',
                ],
            ]),
            'open' => array_merge($baseMessage, [
                'eventType' => 'Open',
                'open' => [
                    'ipAddress' => '127.0.0.1',
                    'timestamp' => now()->toISOString(),
                    'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                ],
            ]),
            'click' => array_merge($baseMessage, [
                'eventType' => 'Click',
                'click' => [
                    'ipAddress' => '127.0.0.1',
                    'timestamp' => now()->toISOString(),
                    'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                    'link' => 'https://example.com/article',
                ],
            ]),
            default => null,
        };
    }
}
