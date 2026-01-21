<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DedicatedIpPoolLow extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $availableCount,
        public int $threshold
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
        return (new MailMessage)
            ->subject('Dedicated IP Pool Running Low')
            ->error()
            ->greeting('Attention Required!')
            ->line('The available dedicated IP pool is running low.')
            ->line('**Current Status:**')
            ->line("- Available IPs: {$this->availableCount}")
            ->line("- Alert Threshold: {$this->threshold}")
            ->line('**Action Required:**')
            ->line('Please purchase additional dedicated IPs from AWS SES to ensure new Pro customers can be provisioned without delay.')
            ->line('**How to Purchase:**')
            ->line('1. Go to AWS SES Console -> Dedicated IPs')
            ->line('2. Click "Request dedicated IP"')
            ->line('3. Add the new IP to the "available-pool"')
            ->line('4. Run `php artisan dedicated-ip:check-pool --sync` to sync')
            ->action('AWS SES Console', 'https://console.aws.amazon.com/ses/home')
            ->line('Each dedicated IP costs $24.95/month.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'available_count' => $this->availableCount,
            'threshold' => $this->threshold,
        ];
    }
}
