<?php

namespace App\Notifications;

use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackReceivedNotification extends Notification implements ShouldQueue
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
        $userName = $this->feedback->user->name;
        $type = $this->feedback->type->label();
        $priority = $this->feedback->priority->label();
        $brandName = $this->feedback->brand?->name ?? 'No brand selected';

        return (new MailMessage)
            ->subject("New Feedback Submitted - {$type}")
            ->greeting('New Feedback Received')
            ->line("**{$userName}** has submitted new feedback.")
            ->line("**Type:** {$type}")
            ->line("**Priority:** {$priority}")
            ->line("**Brand:** {$brandName}")
            ->line('**Description:**')
            ->line($this->feedback->description)
            ->line("**Page URL:** {$this->feedback->page_url}")
            ->action('View Feedback', url("/admin/feedback/{$this->feedback->id}"))
            ->line('Please review and respond to this feedback.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'feedback_id' => $this->feedback->id,
            'type' => $this->feedback->type->value,
            'priority' => $this->feedback->priority->value,
        ];
    }
}
