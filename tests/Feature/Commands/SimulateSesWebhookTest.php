<?php

use App\Models\EmailEvent;
use App\Services\SesEventProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('command has correct signature', function () {
    $this->artisan('ses:simulate --help')
        ->expectsOutputToContain('Simulate SES webhook events')
        ->assertExitCode(0);
});

test('command exists and can be listed', function () {
    $this->artisan('list')
        ->expectsOutputToContain('ses:simulate')
        ->assertExitCode(0);
});

test('command returns failure for unknown event type', function () {
    $this->artisan('ses:simulate', ['event' => 'unknown'])
        ->expectsOutputToContain('Unknown event type')
        ->expectsOutputToContain('Valid types')
        ->assertExitCode(1);
});

test('command simulates bounce event', function () {
    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return $message['eventType'] === 'Bounce'
                    && $message['bounce']['bounceType'] === 'Permanent';
            });
    });

    $this->artisan('ses:simulate', [
        'event' => 'bounce',
        '--message-id' => 'test-message-123',
    ])
        ->expectsOutputToContain('Simulating bounce event')
        ->assertExitCode(0);
});

test('command simulates soft bounce event', function () {
    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return $message['eventType'] === 'Bounce'
                    && $message['bounce']['bounceType'] === 'Transient';
            });
    });

    $this->artisan('ses:simulate', [
        'event' => 'soft-bounce',
        '--message-id' => 'test-message-123',
    ])
        ->expectsOutputToContain('Simulating soft-bounce event')
        ->assertExitCode(0);
});

test('command simulates complaint event', function () {
    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return $message['eventType'] === 'Complaint';
            });
    });

    $this->artisan('ses:simulate', [
        'event' => 'complaint',
        '--message-id' => 'test-message-123',
    ])
        ->expectsOutputToContain('Simulating complaint event')
        ->assertExitCode(0);
});

test('command simulates delivery event', function () {
    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return $message['eventType'] === 'Delivery';
            });
    });

    $this->artisan('ses:simulate', [
        'event' => 'delivery',
        '--message-id' => 'test-message-123',
    ])
        ->expectsOutputToContain('Simulating delivery event')
        ->assertExitCode(0);
});

test('command simulates open event', function () {
    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return $message['eventType'] === 'Open';
            });
    });

    $this->artisan('ses:simulate', [
        'event' => 'open',
        '--message-id' => 'test-message-123',
    ])
        ->expectsOutputToContain('Simulating open event')
        ->assertExitCode(0);
});

test('command simulates click event', function () {
    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return $message['eventType'] === 'Click'
                    && isset($message['click']['link']);
            });
    });

    $this->artisan('ses:simulate', [
        'event' => 'click',
        '--message-id' => 'test-message-123',
    ])
        ->expectsOutputToContain('Simulating click event')
        ->assertExitCode(0);
});

test('command uses most recent sent event message id when not provided', function () {
    // Create a sent email event
    EmailEvent::create([
        'ses_message_id' => 'existing-message-123',
        'event_type' => 'sent',
        'recipient_email' => 'test@example.com',
        'event_at' => now(),
        'event_data' => [],
    ]);

    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return $message['mail']['messageId'] === 'existing-message-123';
            });
    });

    $this->artisan('ses:simulate', ['event' => 'delivery'])
        ->expectsOutputToContain('Using most recent message ID')
        ->assertExitCode(0);
});

test('command generates message id when no sent events exist', function () {
    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return str_starts_with($message['mail']['messageId'], 'test-message-');
            });
    });

    $this->artisan('ses:simulate', ['event' => 'delivery'])
        ->expectsOutputToContain('No sent events found')
        ->assertExitCode(0);
});

test('command uses custom email option', function () {
    $this->mock(SesEventProcessor::class, function (MockInterface $mock) {
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function ($message) {
                return $message['mail']['destination'][0] === 'custom@example.com';
            });
    });

    $this->artisan('ses:simulate', [
        'event' => 'delivery',
        '--message-id' => 'test-123',
        '--email' => 'custom@example.com',
    ])
        ->assertExitCode(0);
});
