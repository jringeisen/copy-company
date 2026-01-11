<?php

namespace App\Mail;

use App\Models\AccountInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public AccountInvitation $invitation
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        /** @var \App\Models\Account $account */
        $account = $this->invitation->account;

        return new Envelope(
            subject: "You've been invited to join {$account->name} on Copy Company",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        /** @var \App\Models\Account $account */
        $account = $this->invitation->account;
        /** @var \App\Models\User $inviter */
        $inviter = $this->invitation->inviter;

        return new Content(
            markdown: 'emails.account-invitation',
            with: [
                'accountName' => $account->name,
                'inviterName' => $inviter->name,
                'role' => ucfirst($this->invitation->role),
                'acceptUrl' => route('invitations.accept', ['token' => $this->invitation->token]),
                'expiresAt' => $this->invitation->expires_at->format('F j, Y'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
