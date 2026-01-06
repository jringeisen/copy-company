<?php

use App\Enums\SubscriberStatus;
use App\Models\Brand;
use App\Models\EmailEvent;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\SesEventProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->processor = new SesEventProcessor;
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
    $this->post = Post::factory()->forBrand($this->brand)->create();
    $this->subscriber = Subscriber::factory()->forBrand($this->brand)->confirmed()->create();
    $this->newsletterSend = NewsletterSend::factory()
        ->forPost($this->post)
        ->forBrand($this->brand)
        ->sent()
        ->create([
            'opens' => 0,
            'unique_opens' => 0,
            'clicks' => 0,
            'unique_clicks' => 0,
            'failed_count' => 0,
            'unsubscribes' => 0,
        ]);
    $this->messageId = 'test-message-'.uniqid();

    // Create the original "sent" event
    $this->sentEvent = EmailEvent::factory()
        ->sent()
        ->forSubscriber($this->subscriber)
        ->forNewsletterSend($this->newsletterSend)
        ->withMessageId($this->messageId)
        ->create();
});

test('processes hard bounce and marks subscriber as bounced', function () {
    $message = [
        'eventType' => 'Bounce',
        'mail' => ['messageId' => $this->messageId],
        'bounce' => [
            'bounceType' => 'Permanent',
            'bounceSubType' => 'General',
            'bouncedRecipients' => [
                ['emailAddress' => $this->subscriber->email],
            ],
        ],
    ];

    $this->processor->process($message);

    $this->subscriber->refresh();

    expect($this->subscriber->status)->toBe(SubscriberStatus::Bounced);
    expect($this->subscriber->bounce_type)->toBe('hard');
    expect($this->subscriber->last_bounce_at)->not->toBeNull();

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $this->messageId,
        'event_type' => 'bounce',
    ]);

    $this->newsletterSend->refresh();
    expect($this->newsletterSend->failed_count)->toBe(1);
});

test('processes soft bounce and increments counter', function () {
    $message = [
        'eventType' => 'Bounce',
        'mail' => ['messageId' => $this->messageId],
        'bounce' => [
            'bounceType' => 'Transient',
            'bounceSubType' => 'MailboxFull',
            'bouncedRecipients' => [
                ['emailAddress' => $this->subscriber->email],
            ],
        ],
    ];

    $this->processor->process($message);

    $this->subscriber->refresh();

    expect($this->subscriber->status)->toBe(SubscriberStatus::Confirmed);
    expect($this->subscriber->soft_bounce_count)->toBe(1);
    expect($this->subscriber->last_bounce_at)->not->toBeNull();
});

test('marks subscriber as bounced after 3 soft bounces', function () {
    $this->subscriber->update(['soft_bounce_count' => 2]);

    $message = [
        'eventType' => 'Bounce',
        'mail' => ['messageId' => $this->messageId],
        'bounce' => [
            'bounceType' => 'Transient',
            'bounceSubType' => 'MailboxFull',
            'bouncedRecipients' => [
                ['emailAddress' => $this->subscriber->email],
            ],
        ],
    ];

    $this->processor->process($message);

    $this->subscriber->refresh();

    expect($this->subscriber->status)->toBe(SubscriberStatus::Bounced);
    expect($this->subscriber->bounce_type)->toBe('soft');
    expect($this->subscriber->soft_bounce_count)->toBe(3);
});

test('processes complaint and marks subscriber as complained', function () {
    $message = [
        'eventType' => 'Complaint',
        'mail' => ['messageId' => $this->messageId],
        'complaint' => [
            'complainedRecipients' => [
                ['emailAddress' => $this->subscriber->email],
            ],
            'complaintFeedbackType' => 'abuse',
        ],
    ];

    $this->processor->process($message);

    $this->subscriber->refresh();

    expect($this->subscriber->status)->toBe(SubscriberStatus::Complained);

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $this->messageId,
        'event_type' => 'complaint',
    ]);

    $this->newsletterSend->refresh();
    expect($this->newsletterSend->unsubscribes)->toBe(1);
});

test('processes delivery event', function () {
    $message = [
        'eventType' => 'Delivery',
        'mail' => ['messageId' => $this->messageId],
        'delivery' => [
            'recipients' => [$this->subscriber->email],
            'timestamp' => now()->toISOString(),
        ],
    ];

    $this->processor->process($message);

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $this->messageId,
        'event_type' => 'delivery',
    ]);
});

test('processes open event and increments counters', function () {
    $message = [
        'eventType' => 'Open',
        'mail' => ['messageId' => $this->messageId],
        'open' => [
            'ipAddress' => '127.0.0.1',
            'timestamp' => now()->toISOString(),
        ],
    ];

    $this->processor->process($message);

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $this->messageId,
        'event_type' => 'open',
    ]);

    $this->newsletterSend->refresh();
    expect($this->newsletterSend->opens)->toBe(1);
    expect($this->newsletterSend->unique_opens)->toBe(1);
});

test('processes multiple opens and only increments unique_opens once', function () {
    $message = [
        'eventType' => 'Open',
        'mail' => ['messageId' => $this->messageId],
        'open' => [
            'ipAddress' => '127.0.0.1',
            'timestamp' => now()->toISOString(),
        ],
    ];

    // First open
    $this->processor->process($message);

    // Second open (same message)
    $this->processor->process($message);

    $this->newsletterSend->refresh();
    expect($this->newsletterSend->opens)->toBe(2);
    expect($this->newsletterSend->unique_opens)->toBe(1);
});

test('processes click event with link url', function () {
    $linkUrl = 'https://example.com/article';

    $message = [
        'eventType' => 'Click',
        'mail' => ['messageId' => $this->messageId],
        'click' => [
            'ipAddress' => '127.0.0.1',
            'timestamp' => now()->toISOString(),
            'link' => $linkUrl,
        ],
    ];

    $this->processor->process($message);

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $this->messageId,
        'event_type' => 'click',
        'link_url' => $linkUrl,
    ]);

    $this->newsletterSend->refresh();
    expect($this->newsletterSend->clicks)->toBe(1);
    expect($this->newsletterSend->unique_clicks)->toBe(1);
});

test('processes multiple clicks and only increments unique_clicks once', function () {
    $message = [
        'eventType' => 'Click',
        'mail' => ['messageId' => $this->messageId],
        'click' => [
            'ipAddress' => '127.0.0.1',
            'timestamp' => now()->toISOString(),
            'link' => 'https://example.com',
        ],
    ];

    // First click
    $this->processor->process($message);

    // Second click (same message)
    $this->processor->process($message);

    $this->newsletterSend->refresh();
    expect($this->newsletterSend->clicks)->toBe(2);
    expect($this->newsletterSend->unique_clicks)->toBe(1);
});

test('handles event without sent event correlation', function () {
    $orphanMessageId = 'orphan-message-'.uniqid();

    $message = [
        'eventType' => 'Delivery',
        'mail' => ['messageId' => $orphanMessageId],
        'delivery' => [
            'recipients' => ['unknown@example.com'],
        ],
    ];

    $this->processor->process($message);

    // Should still create the event, just without subscriber/newsletter correlation
    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $orphanMessageId,
        'event_type' => 'delivery',
        'subscriber_id' => null,
        'newsletter_send_id' => null,
    ]);
});

test('ignores messages without message id', function () {
    $message = [
        'eventType' => 'Delivery',
        'mail' => [], // No messageId
    ];

    $this->processor->process($message);

    // No events should be created
    expect(EmailEvent::where('event_type', 'delivery')->count())->toBe(0);
});

test('handles unknown event types gracefully', function () {
    $message = [
        'eventType' => 'UnknownType',
        'mail' => ['messageId' => $this->messageId],
    ];

    // Should not throw an exception
    $this->processor->process($message);

    // No new events should be created
    expect(EmailEvent::where('ses_message_id', $this->messageId)->count())->toBe(1); // Only the original sent event
});
