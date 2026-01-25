<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DisputeResolvedNotification extends Notification implements ShouldQueue
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
        $resolution = $this->dispute->resolution ?? 'closed';
        $isWon = $resolution === 'won';
        $accountName = $this->dispute->account?->name ?? 'Unknown Account';

        $mail = (new MailMessage)
            ->subject('Dispute '.$this->getResolutionTitle()." - {$accountName}")
            ->greeting('Dispute Resolution Update');

        if ($isWon) {
            $mail->line("The payment dispute for **{$accountName}** has been resolved in our favor.")
                ->line('**Dispute Outcome:**')
                ->line("- Account: {$accountName}")
                ->line("- Amount: {$amount}")
                ->line('- Resolution: Won')
                ->line('- Funds Status: Reinstated')
                ->line('No further action is required.');
        } else {
            $mail->error()
                ->line("The payment dispute for **{$accountName}** has been resolved against us.")
                ->line('**Dispute Outcome:**')
                ->line("- Account: {$accountName}")
                ->line("- Amount: {$amount}")
                ->line("- Resolution: {$resolution}")
                ->line('The disputed amount has been deducted from the account.');
        }

        return $mail->action('View in Admin Panel', url('/admin/disputes/'.$this->dispute->id));
    }

    protected function getResolutionTitle(): string
    {
        return match ($this->dispute->resolution) {
            'won' => 'Won',
            'lost' => 'Lost',
            'warning_closed' => 'Closed (Warning)',
            default => 'Resolved',
        };
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
            'resolution' => $this->dispute->resolution,
        ];
    }
}
