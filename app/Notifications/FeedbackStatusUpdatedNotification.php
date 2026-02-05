<?php

namespace App\Notifications;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Feedback $feedback
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
        $statusLabel = $this->feedback->status->label();
        $type = $this->feedback->type->label();

        $message = (new MailMessage)
            ->subject("Feedback Status Updated - {$statusLabel}")
            ->greeting('Feedback Status Update')
            ->line("Your feedback submission has been updated to: **{$statusLabel}**")
            ->line("**Type:** {$type}")
            ->line('**Your Feedback:**')
            ->line($this->feedback->description);

        if ($this->feedback->admin_notes) {
            $message->line('**Response from our team:**')
                ->line($this->feedback->admin_notes);
        }

        $message->action('View Feedback', url('/feedback'))
            ->line('Thank you for helping us improve Copy Company!');

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'feedback_id' => $this->feedback->id,
            'status' => $this->feedback->status->value,
        ];
    }
}
