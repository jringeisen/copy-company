<?php

use App\Models\Brand;
use App\Models\EmailEvent;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
    $this->post = Post::factory()->forBrand($this->brand)->create();
    $this->subscriber = Subscriber::factory()->forBrand($this->brand)->confirmed()->create();
    $this->newsletterSend = NewsletterSend::factory()
        ->forPost($this->post)
        ->forBrand($this->brand)
        ->create();
});

test('ses webhook handles subscription confirmation', function () {
    Http::fake([
        'https://sns.amazonaws.com/*' => Http::response('', 200),
    ]);

    $payload = [
        'Type' => 'SubscriptionConfirmation',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789:wordsmith-ses-events',
        'SubscribeURL' => 'https://sns.amazonaws.com/confirm?token=abc123',
    ];

    $response = $this->postJson('/webhooks/ses', $payload);

    $response->assertStatus(200);
    $response->assertSee('Confirmed');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://sns.amazonaws.com/confirm?token=abc123';
    });
});

test('ses webhook rejects invalid json', function () {
    $response = $this->post('/webhooks/ses', [], [
        'Content-Type' => 'application/json',
    ]);

    $response->assertStatus(400);
});

test('ses webhook processes bounce notification', function () {
    $messageId = 'test-message-'.uniqid();

    // Create the original sent event
    EmailEvent::factory()
        ->sent()
        ->forSubscriber($this->subscriber)
        ->forNewsletterSend($this->newsletterSend)
        ->withMessageId($messageId)
        ->create();

    $payload = [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Bounce',
            'mail' => [
                'messageId' => $messageId,
                'destination' => [$this->subscriber->email],
            ],
            'bounce' => [
                'bounceType' => 'Permanent',
                'bounceSubType' => 'General',
                'bouncedRecipients' => [
                    ['emailAddress' => $this->subscriber->email],
                ],
            ],
        ]),
    ];

    $response = $this->postJson('/webhooks/ses', $payload);

    $response->assertStatus(200);

    // Verify bounce event was created
    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $messageId,
        'event_type' => 'bounce',
    ]);
});

test('ses webhook processes delivery notification', function () {
    $messageId = 'test-message-'.uniqid();

    EmailEvent::factory()
        ->sent()
        ->forSubscriber($this->subscriber)
        ->forNewsletterSend($this->newsletterSend)
        ->withMessageId($messageId)
        ->create();

    $payload = [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Delivery',
            'mail' => [
                'messageId' => $messageId,
                'destination' => [$this->subscriber->email],
            ],
            'delivery' => [
                'recipients' => [$this->subscriber->email],
                'timestamp' => now()->toISOString(),
            ],
        ]),
    ];

    $response = $this->postJson('/webhooks/ses', $payload);

    $response->assertStatus(200);

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $messageId,
        'event_type' => 'delivery',
    ]);
});

test('ses webhook processes open notification', function () {
    $messageId = 'test-message-'.uniqid();

    EmailEvent::factory()
        ->sent()
        ->forSubscriber($this->subscriber)
        ->forNewsletterSend($this->newsletterSend)
        ->withMessageId($messageId)
        ->create();

    $payload = [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Open',
            'mail' => [
                'messageId' => $messageId,
            ],
            'open' => [
                'ipAddress' => '127.0.0.1',
                'timestamp' => now()->toISOString(),
            ],
        ]),
    ];

    $response = $this->postJson('/webhooks/ses', $payload);

    $response->assertStatus(200);

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $messageId,
        'event_type' => 'open',
    ]);
});

test('ses webhook processes click notification', function () {
    $messageId = 'test-message-'.uniqid();

    EmailEvent::factory()
        ->sent()
        ->forSubscriber($this->subscriber)
        ->forNewsletterSend($this->newsletterSend)
        ->withMessageId($messageId)
        ->create();

    $payload = [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Click',
            'mail' => [
                'messageId' => $messageId,
            ],
            'click' => [
                'ipAddress' => '127.0.0.1',
                'timestamp' => now()->toISOString(),
                'link' => 'https://example.com/article',
            ],
        ]),
    ];

    $response = $this->postJson('/webhooks/ses', $payload);

    $response->assertStatus(200);

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $messageId,
        'event_type' => 'click',
        'link_url' => 'https://example.com/article',
    ]);
});

test('ses webhook processes complaint notification', function () {
    $messageId = 'test-message-'.uniqid();

    EmailEvent::factory()
        ->sent()
        ->forSubscriber($this->subscriber)
        ->forNewsletterSend($this->newsletterSend)
        ->withMessageId($messageId)
        ->create();

    $payload = [
        'Type' => 'Notification',
        'Message' => json_encode([
            'eventType' => 'Complaint',
            'mail' => [
                'messageId' => $messageId,
            ],
            'complaint' => [
                'complainedRecipients' => [
                    ['emailAddress' => $this->subscriber->email],
                ],
                'complaintFeedbackType' => 'abuse',
            ],
        ]),
    ];

    $response = $this->postJson('/webhooks/ses', $payload);

    $response->assertStatus(200);

    $this->assertDatabaseHas('email_events', [
        'ses_message_id' => $messageId,
        'event_type' => 'complaint',
    ]);
});

test('ses webhook returns ok for unknown notification types', function () {
    $payload = [
        'Type' => 'UnknownType',
    ];

    $response = $this->postJson('/webhooks/ses', $payload);

    $response->assertStatus(200);
});

test('ses webhook returns error for notification without message', function () {
    $payload = [
        'Type' => 'Notification',
        // Missing 'Message' field
    ];

    $response = $this->postJson('/webhooks/ses', $payload);

    $response->assertStatus(400);
});
