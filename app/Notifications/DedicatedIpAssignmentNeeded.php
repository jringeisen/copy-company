<?php

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DedicatedIpAssignmentNeeded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Account $account
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
        $brandNames = $this->account->brands->pluck('name')->join(', ');
        $ownerEmail = $this->account->users()->first()?->email ?? 'unknown';

        return (new MailMessage)
            ->subject('New Pro Subscription - IP Assignment Needed')
            ->greeting('New Pro Customer Alert!')
            ->line('A new Pro subscription has been activated and requires dedicated IP assignment.')
            ->line('**Account Details:**')
            ->line("- Account ID: {$this->account->id}")
            ->line("- Owner Email: {$ownerEmail}")
            ->line("- Brands: {$brandNames}")
            ->line('**Action Required:**')
            ->line('1. Go to the admin panel')
            ->line('2. Navigate to Dedicated IPs section')
            ->line('3. Assign an available IP to each brand')
            ->action('Admin Panel', url('/admin/dedicated-ips'))
            ->line('Note: The brand(s) are currently in "provisioning" status and AWS resources have been created. An IP just needs to be assigned.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'account_id' => $this->account->id,
            'brand_count' => $this->account->brands->count(),
        ];
    }
}
