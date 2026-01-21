<?php

namespace App\Console\Commands;

use App\Models\DedicatedIp;
use App\Models\User;
use App\Notifications\DedicatedIpPoolLow;
use App\Services\SesDedicatedIpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckDedicatedIpPool extends Command
{
    protected $signature = 'dedicated-ip:check-pool {--sync : Sync IPs from AWS}';

    protected $description = 'Check dedicated IP pool availability and alert if running low';

    public function handle(SesDedicatedIpService $service): int
    {
        // Optionally sync IPs from AWS
        if ($this->option('sync')) {
            $this->info('Syncing IPs from AWS...');
            $service->syncDedicatedIps();
        }

        $availableCount = DedicatedIp::available()->count();
        $assignedCount = DedicatedIp::assigned()->count();
        $totalCount = DedicatedIp::count();

        $this->info('Dedicated IP Pool Status');
        $this->info('========================');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total IPs', $totalCount],
                ['Available', $availableCount],
                ['Assigned', $assignedCount],
            ]
        );

        $minAvailable = config('services.ses.min_available_ips_alert', 3);

        if ($availableCount < $minAvailable) {
            $this->warn("WARNING: Only {$availableCount} IPs available (threshold: {$minAvailable})");

            Log::warning('Dedicated IP pool running low', [
                'available' => $availableCount,
                'threshold' => $minAvailable,
            ]);

            // Notify admins
            $this->notifyAdmins($availableCount, $minAvailable);
        } else {
            $this->info('Pool status: Healthy');
        }

        return Command::SUCCESS;
    }

    private function notifyAdmins(int $available, int $threshold): void
    {
        // Get admin users - you can customize this query based on your user roles
        $admins = User::where('email', 'like', '%@'.parse_url(config('app.url'), PHP_URL_HOST))->get();

        if ($admins->isEmpty()) {
            return;
        }

        try {
            Notification::send($admins, new DedicatedIpPoolLow($available, $threshold));
            $this->info('Admin notification sent.');
        } catch (\Exception $e) {
            Log::error('Failed to send pool low notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
