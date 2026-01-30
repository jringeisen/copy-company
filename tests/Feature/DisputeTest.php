<?php

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Jobs\GatherDisputeEvidence;
use App\Jobs\ProcessDisputeClosed;
use App\Jobs\ProcessDisputeCreated;
use App\Jobs\ProcessDisputeUpdated;
use App\Models\Account;
use App\Models\Dispute;
use App\Models\DisputeEvent;
use App\Models\User;
use App\Notifications\DisputeCreatedNotification;
use App\Notifications\DisputeResolvedNotification;
use App\Services\DisputeService;
use App\Services\EvidenceGatheringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// DisputeStatus Enum Tests

test('dispute status has correct labels', function () {
    expect(DisputeStatus::NeedsResponse->label())->toBe('Needs Response');
    expect(DisputeStatus::UnderReview->label())->toBe('Under Review');
    expect(DisputeStatus::Won->label())->toBe('Won');
    expect(DisputeStatus::Lost->label())->toBe('Lost');
    expect(DisputeStatus::WarningNeedsResponse->label())->toBe('Warning - Needs Response');
});

test('dispute status knows if resolved', function () {
    expect(DisputeStatus::Won->isResolved())->toBeTrue();
    expect(DisputeStatus::Lost->isResolved())->toBeTrue();
    expect(DisputeStatus::WarningClosed->isResolved())->toBeTrue();
    expect(DisputeStatus::NeedsResponse->isResolved())->toBeFalse();
    expect(DisputeStatus::UnderReview->isResolved())->toBeFalse();
});

test('dispute status knows if requires action', function () {
    expect(DisputeStatus::NeedsResponse->requiresAction())->toBeTrue();
    expect(DisputeStatus::WarningNeedsResponse->requiresAction())->toBeTrue();
    expect(DisputeStatus::UnderReview->requiresAction())->toBeFalse();
    expect(DisputeStatus::Won->requiresAction())->toBeFalse();
});

test('dispute status has correct colors', function () {
    expect(DisputeStatus::NeedsResponse->color())->toBe('red');
    expect(DisputeStatus::UnderReview->color())->toBe('yellow');
    expect(DisputeStatus::Won->color())->toBe('green');
    expect(DisputeStatus::Lost->color())->toBe('gray');
});

// DisputeReason Enum Tests

test('dispute reason has correct labels', function () {
    expect(DisputeReason::Fraudulent->label())->toBe('Fraudulent');
    expect(DisputeReason::ProductNotReceived->label())->toBe('Product Not Received');
    expect(DisputeReason::SubscriptionCanceled->label())->toBe('Subscription Canceled');
    expect(DisputeReason::Duplicate->label())->toBe('Duplicate Charge');
});

test('dispute reason provides recommended evidence', function () {
    $evidence = DisputeReason::Fraudulent->recommendedEvidence();

    expect($evidence)->toBeArray();
    expect($evidence)->toContain('customer_communication');
});

// Dispute Model Tests

test('dispute can be created with factory', function () {
    $dispute = Dispute::factory()->create();

    expect($dispute)->toBeInstanceOf(Dispute::class);
    expect($dispute->stripe_dispute_id)->toStartWith('dp_');
});

test('dispute belongs to account', function () {
    $account = Account::factory()->create();
    $dispute = Dispute::factory()->create(['account_id' => $account->id]);

    expect($dispute->account->id)->toBe($account->id);
});

test('dispute can record events', function () {
    $dispute = Dispute::factory()->create();

    $event = $dispute->recordEvent('test.event', ['test' => 'data']);

    expect($event)->toBeInstanceOf(DisputeEvent::class);
    expect($event->event_type)->toBe('test.event');
    expect($event->event_data)->toBe(['test' => 'data']);
});

test('dispute has events relationship', function () {
    $dispute = Dispute::factory()->create();
    DisputeEvent::factory()->count(3)->create(['dispute_id' => $dispute->id]);

    expect($dispute->events)->toHaveCount(3);
});

test('dispute knows if resolved', function () {
    $wonDispute = Dispute::factory()->won()->create();
    $lostDispute = Dispute::factory()->lost()->create();
    $pendingDispute = Dispute::factory()->needsResponse()->create();

    expect($wonDispute->isResolved())->toBeTrue();
    expect($lostDispute->isResolved())->toBeTrue();
    expect($pendingDispute->isResolved())->toBeFalse();
});

test('dispute knows if requires action', function () {
    $needsResponse = Dispute::factory()->needsResponse()->create();
    $underReview = Dispute::factory()->underReview()->create();

    expect($needsResponse->requiresAction())->toBeTrue();
    expect($underReview->requiresAction())->toBeFalse();
});

test('dispute formats amount correctly', function () {
    $dispute = Dispute::factory()->create(['amount' => 12345]);

    expect($dispute->formattedAmount())->toBe('123.45');
});

test('dispute knows if evidence is overdue', function () {
    $overdueDispute = Dispute::factory()->create([
        'evidence_due_at' => now()->subDay(),
    ]);
    $upcomingDispute = Dispute::factory()->create([
        'evidence_due_at' => now()->addDay(),
    ]);

    expect($overdueDispute->isEvidenceOverdue())->toBeTrue();
    expect($upcomingDispute->isEvidenceOverdue())->toBeFalse();
});

test('dispute open scope returns unresolved disputes', function () {
    Dispute::factory()->needsResponse()->create();
    Dispute::factory()->underReview()->create();
    Dispute::factory()->won()->create();
    Dispute::factory()->lost()->create();

    $openDisputes = Dispute::query()->open()->get();

    expect($openDisputes)->toHaveCount(2);
});

test('dispute requiring action scope returns correct disputes', function () {
    Dispute::factory()->needsResponse()->create();
    Dispute::factory()->underReview()->create();
    Dispute::factory()->warning()->create();

    $requiringAction = Dispute::query()->requiringAction()->get();

    expect($requiringAction)->toHaveCount(2);
});

// DisputeService Tests

test('dispute service creates dispute from stripe data', function () {
    $account = Account::factory()->create(['stripe_id' => 'cus_test123']);
    $disputeService = app(DisputeService::class);

    $stripeDispute = [
        'id' => 'dp_test123',
        'charge' => 'ch_test123',
        'payment_intent' => 'pi_test123',
        'amount' => 5000,
        'currency' => 'usd',
        'status' => 'needs_response',
        'reason' => 'fraudulent',
        'evidence_details' => [
            'due_by' => now()->addDays(7)->timestamp,
            'customer_id' => 'cus_test123',
        ],
        'created' => now()->timestamp,
    ];

    $dispute = $disputeService->createOrUpdateFromStripe($stripeDispute);

    expect($dispute)->not->toBeNull();
    expect($dispute->stripe_dispute_id)->toBe('dp_test123');
    expect($dispute->account_id)->toBe($account->id);
    expect($dispute->amount)->toBe(5000);
    expect($dispute->status)->toBe(DisputeStatus::NeedsResponse);
    expect($dispute->reason)->toBe(DisputeReason::Fraudulent);
});

test('dispute service updates dispute status', function () {
    $dispute = Dispute::factory()->needsResponse()->create();
    $disputeService = app(DisputeService::class);

    $stripeDispute = [
        'status' => 'under_review',
    ];

    $updated = $disputeService->updateStatus($dispute, $stripeDispute);

    expect($updated->status)->toBe(DisputeStatus::UnderReview);
});

test('dispute service marks dispute as closed', function () {
    $dispute = Dispute::factory()->needsResponse()->create();
    $disputeService = app(DisputeService::class);

    $stripeDispute = [
        'status' => 'won',
    ];

    $closed = $disputeService->markClosed($dispute, $stripeDispute);

    expect($closed->status)->toBe(DisputeStatus::Won);
    expect($closed->resolution)->toBe('won');
    expect($closed->resolved_at)->not->toBeNull();
});

test('dispute service marks funds withdrawn', function () {
    $dispute = Dispute::factory()->create(['funds_withdrawn' => false]);
    $disputeService = app(DisputeService::class);

    $updated = $disputeService->markFundsWithdrawn($dispute);

    expect($updated->funds_withdrawn)->toBeTrue();
});

test('dispute service marks funds reinstated', function () {
    $dispute = Dispute::factory()->create(['funds_reinstated' => false]);
    $disputeService = app(DisputeService::class);

    $updated = $disputeService->markFundsReinstated($dispute);

    expect($updated->funds_reinstated)->toBeTrue();
});

// EvidenceGatheringService Tests

test('evidence gathering service collects evidence', function () {
    $account = Account::factory()->create(['name' => 'Test Account']);
    $user = User::factory()->create(['email' => 'admin@test.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $dispute = Dispute::factory()->create(['account_id' => $account->id]);

    $evidenceService = app(EvidenceGatheringService::class);
    $evidence = $evidenceService->gatherEvidence($dispute);

    expect($evidence)->toBeArray();
    expect($evidence['customer_email_address'])->toBe('admin@test.com');
    expect($evidence['customer_name'])->toBe('Test Account');
    expect($evidence)->toHaveKey('product_description');
    expect($evidence)->toHaveKey('cancellation_policy');
});

// Job Tests

test('process dispute created job dispatches gather evidence job', function () {
    Queue::fake([GatherDisputeEvidence::class]);
    Notification::fake();

    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create(['stripe_id' => 'cus_test123']);

    $payload = [
        'data' => [
            'object' => [
                'id' => 'dp_test123',
                'charge' => 'ch_test123',
                'payment_intent' => 'pi_test123',
                'amount' => 5000,
                'currency' => 'usd',
                'status' => 'needs_response',
                'reason' => 'fraudulent',
                'evidence_details' => [
                    'due_by' => now()->addDays(7)->timestamp,
                    'customer_id' => 'cus_test123',
                ],
                'created' => now()->timestamp,
            ],
        ],
    ];

    $job = new ProcessDisputeCreated($payload);
    $job->handle(app(DisputeService::class));

    Queue::assertPushed(GatherDisputeEvidence::class);
    Notification::assertSentOnDemand(DisputeCreatedNotification::class, function ($notification, $channels, $notifiable) {
        return $notifiable->routes['mail'] === 'admin@platform.com';
    });
});

test('process dispute updated job updates dispute status', function () {
    $dispute = Dispute::factory()->needsResponse()->create();

    $payload = [
        'data' => [
            'object' => [
                'id' => $dispute->stripe_dispute_id,
                'status' => 'under_review',
            ],
        ],
    ];

    $job = new ProcessDisputeUpdated($payload);
    $job->handle(app(DisputeService::class));

    $dispute->refresh();
    expect($dispute->status)->toBe(DisputeStatus::UnderReview);
});

test('process dispute closed job notifies platform admins', function () {
    Notification::fake();

    config(['admin.emails' => ['admin@platform.com', 'support@platform.com']]);

    $account = Account::factory()->create();
    $dispute = Dispute::factory()->create(['account_id' => $account->id]);

    $payload = [
        'data' => [
            'object' => [
                'id' => $dispute->stripe_dispute_id,
                'status' => 'won',
            ],
        ],
    ];

    $job = new ProcessDisputeClosed($payload);
    $job->handle(app(DisputeService::class));

    $dispute->refresh();
    expect($dispute->status)->toBe(DisputeStatus::Won);

    Notification::assertSentOnDemand(DisputeResolvedNotification::class, function ($notification, $channels, $notifiable) {
        return in_array($notifiable->routes['mail'], ['admin@platform.com', 'support@platform.com']);
    });
});

// Account Relationship Test

test('account has disputes relationship', function () {
    $account = Account::factory()->create();
    Dispute::factory()->count(3)->create(['account_id' => $account->id]);

    expect($account->disputes)->toHaveCount(3);
});

// Admin Controller Tests

test('admin disputes index page requires authentication', function () {
    $response = $this->get('/admin/disputes');

    $response->assertRedirect('/login');
});

test('admin disputes index page requires admin access', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/admin/disputes');

    $response->assertForbidden();
});

test('admin disputes index page displays for platform admin', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/admin/disputes');

    $response->assertSuccessful();
});

test('admin disputes show page requires admin access', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'user@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $dispute = Dispute::factory()->create(['account_id' => $account->id]);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get("/admin/disputes/{$dispute->id}");

    $response->assertForbidden();
});

test('admin disputes show page displays dispute details', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'admin@platform.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $dispute = Dispute::factory()->create(['account_id' => $account->id]);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get("/admin/disputes/{$dispute->id}");

    $response->assertSuccessful();
});

test('admin can view disputes from any account', function () {
    config(['admin.emails' => ['admin@platform.com']]);

    $account1 = Account::factory()->create();
    $account2 = Account::factory()->create();
    $user = User::factory()->create(['email' => 'admin@platform.com']);
    $account1->users()->attach($user->id, ['role' => 'admin']);

    $dispute = Dispute::factory()->create(['account_id' => $account2->id]);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account1->id]);
    $response = $this->get("/admin/disputes/{$dispute->id}");

    $response->assertSuccessful();
});

test('local environment allows admin access without admin email', function () {
    config(['admin.emails' => []]);
    app()->detectEnvironment(fn () => 'local');

    $account = Account::factory()->create();
    $user = User::factory()->create(['email' => 'anyuser@example.com']);
    $account->users()->attach($user->id, ['role' => 'admin']);

    $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id]);
    $response = $this->get('/admin/disputes');

    $response->assertSuccessful();
});
