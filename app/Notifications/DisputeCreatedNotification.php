<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Dispute $dispute
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = '$'.$this->dispute->formattedAmount().' '.strtoupper($this->dispute->currency);
        $reason = $this->dispute->reason->label();
        $dueDate = $this->dispute->evidence_due_at?->format('M d, Y g:i A') ?? 'N/A';
        $accountName = $this->dispute->account?->name ?? 'Unknown Account';

        return (new MailMessage)
            ->subject('New Payment Dispute - Action Required')
            ->error()
            ->greeting('New Dispute Alert')
            ->line("A payment dispute has been filed against **{$accountName}**.")
            ->line('**Dispute Details:**')
            ->line("- Account: {$accountName}")
            ->line("- Amount: {$amount}")
            ->line("- Reason: {$reason}")
            ->line("- Evidence Due: {$dueDate}")
            ->line("- Stripe ID: {$this->dispute->stripe_dispute_id}")
            ->line('**Automated Actions:**')
            ->line('- Evidence gathering job has been dispatched')
            ->line('- Evidence will be automatically submitted to Stripe')
            ->action('View in Admin Panel', url('/admin/disputes/'.$this->dispute->id))
            ->line('Review the dispute details and ensure evidence is properly submitted before the deadline.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'stripe_dispute_id' => $this->dispute->stripe_dispute_id,
            'amount' => $this->dispute->amount,
            'reason' => $this->dispute->reason->value,
        ];
    }
}
