<?php

namespace App\Notifications;

use App\Models\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DedicatedIpSuspended extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{bounce_rate: float, complaint_rate: float, sends: int, bounces: int, complaints: int}  $metrics
     */
    public function __construct(
        public Brand $brand,
        public array $metrics
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
        $bounceRate = round($this->metrics['bounce_rate'] * 100, 2);
        $complaintRate = round($this->metrics['complaint_rate'] * 100, 3);

        return (new MailMessage)
            ->subject('Dedicated IP Suspended - Action Required')
            ->error()
            ->greeting("Hello {$notifiable->name},")
            ->line("Your dedicated IP for **{$this->brand->name}** has been temporarily suspended due to reputation concerns.")
            ->line('**Current Metrics (Last 24 Hours):**')
            ->line("- Bounce Rate: {$bounceRate}% (threshold: 5%)")
            ->line("- Complaint Rate: {$complaintRate}% (threshold: 0.1%)")
            ->line("- Total Sends: {$this->metrics['sends']}")
            ->line('**What This Means:**')
            ->line('Your emails will continue to be sent via our shared IP pool to ensure deliverability. Your dedicated IP will not be used until the issue is resolved.')
            ->line('**Recommended Actions:**')
            ->line('1. Review your subscriber list for invalid or inactive addresses')
            ->line('2. Ensure you have proper unsubscribe links in all emails')
            ->line('3. Consider implementing double opt-in for new subscribers')
            ->action('View Email Settings', url('/settings/email'))
            ->line('Once you have addressed these issues, please contact support to have your dedicated IP reactivated.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'brand_id' => $this->brand->id,
            'brand_name' => $this->brand->name,
            'metrics' => $this->metrics,
        ];
    }
}
