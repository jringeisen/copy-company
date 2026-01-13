<?php

use App\Mail\AccountInvitationMail;
use App\Models\Account;
use App\Models\AccountInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->inviter = User::factory()->create(['name' => 'John Inviter']);
    $this->account = Account::factory()->create(['name' => 'Test Account']);
    $this->account->users()->attach($this->inviter->id, ['role' => 'admin']);

    $this->invitation = AccountInvitation::create([
        'account_id' => $this->account->id,
        'invited_by' => $this->inviter->id,
        'email' => 'invited@example.com',
        'role' => 'member',
        'token' => 'test-invitation-token',
        'expires_at' => now()->addDays(7),
    ]);
});

test('it sets correct subject', function () {
    $mail = new AccountInvitationMail($this->invitation);

    $envelope = $mail->envelope();

    expect($envelope->subject)->toBe("You've been invited to join Test Account on Copy Company");
});

test('it includes correct content data', function () {
    $mail = new AccountInvitationMail($this->invitation);

    $content = $mail->content();

    expect($content->markdown)->toBe('emails.account-invitation')
        ->and($content->with['accountName'])->toBe('Test Account')
        ->and($content->with['inviterName'])->toBe('John Inviter')
        ->and($content->with['role'])->toBe('Member');
});

test('it includes accept url with token', function () {
    $mail = new AccountInvitationMail($this->invitation);

    $content = $mail->content();

    $expectedUrl = route('invitations.accept', ['token' => 'test-invitation-token']);

    expect($content->with['acceptUrl'])->toBe($expectedUrl);
});

test('it includes formatted expiration date', function () {
    $mail = new AccountInvitationMail($this->invitation);

    $content = $mail->content();

    $expectedDate = $this->invitation->expires_at->format('F j, Y');

    expect($content->with['expiresAt'])->toBe($expectedDate);
});

test('it has no attachments', function () {
    $mail = new AccountInvitationMail($this->invitation);

    $attachments = $mail->attachments();

    expect($attachments)->toBeEmpty();
});

test('it can be rendered', function () {
    $mail = new AccountInvitationMail($this->invitation);

    // Ensure it renders without error
    $mail->render();

    expect(true)->toBeTrue();
});

test('it capitalizes role name', function () {
    $invitation = AccountInvitation::create([
        'account_id' => $this->account->id,
        'invited_by' => $this->inviter->id,
        'email' => 'admin@example.com',
        'role' => 'admin',
        'token' => 'admin-token',
        'expires_at' => now()->addDays(7),
    ]);

    $mail = new AccountInvitationMail($invitation);

    $content = $mail->content();

    expect($content->with['role'])->toBe('Admin');
});
