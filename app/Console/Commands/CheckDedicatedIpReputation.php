<?php

namespace App\Console\Commands;

use App\Enums\DedicatedIpStatus;
use App\Models\Brand;
use App\Models\EmailEvent;
use App\Notifications\DedicatedIpSuspended;
use App\Services\SesDedicatedIpService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckDedicatedIpReputation extends Command
{
    protected $signature = 'dedicated-ip:check-reputation';

    protected $description = 'Check dedicated IP reputation and auto-suspend if thresholds exceeded';

    public function handle(SesDedicatedIpService $service): int
    {
        $brands = Brand::where('dedicated_ip_status', DedicatedIpStatus::Active)
            ->orWhere('dedicated_ip_status', DedicatedIpStatus::Warming)
            ->get();

        $this->info("Checking reputation for {$brands->count()} brands with dedicated IPs...");

        $bounceThreshold = config('services.ses.bounce_rate_threshold', 0.05);
        $complaintThreshold = config('services.ses.complaint_rate_threshold', 0.001);

        foreach ($brands as $brand) {
            $metrics = $this->get24HourMetrics($brand);

            $this->line("Brand {$brand->id}: {$metrics['sends']} sends, {$metrics['bounces']} bounces, {$metrics['complaints']} complaints");

            $hasCriticalIssue = false;

            if ($metrics['sends'] > 0) {
                if ($metrics['bounce_rate'] > $bounceThreshold) {
                    $this->error("Brand {$brand->id}: Bounce rate {$metrics['bounce_rate']} exceeds threshold");
                    $hasCriticalIssue = true;
                }

                if ($metrics['complaint_rate'] > $complaintThreshold) {
                    $this->error("Brand {$brand->id}: Complaint rate {$metrics['complaint_rate']} exceeds threshold");
                    $hasCriticalIssue = true;
                }
            }

            if ($hasCriticalIssue) {
                $this->suspendBrand($brand, $metrics, $service);
            }
        }

        $this->info('Reputation check complete.');

        return Command::SUCCESS;
    }

    /**
     * Get 24-hour metrics for a brand.
     *
     * @return array{sends: int, bounces: int, complaints: int, bounce_rate: float, complaint_rate: float}
     */
    private function get24HourMetrics(Brand $brand): array
    {
        $since = Carbon::now()->subDay();

        $sends = EmailEvent::where('event_type', 'sent')
            ->whereHas('newsletterSend', fn ($q) => $q->where('brand_id', $brand->id))
            ->where('event_at', '>=', $since)
            ->count();

        $bounces = EmailEvent::whereIn('event_type', ['bounce', 'hard_bounce', 'soft_bounce'])
            ->whereHas('newsletterSend', fn ($q) => $q->where('brand_id', $brand->id))
            ->where('event_at', '>=', $since)
            ->count();

        $complaints = EmailEvent::where('event_type', 'complaint')
            ->whereHas('newsletterSend', fn ($q) => $q->where('brand_id', $brand->id))
            ->where('event_at', '>=', $since)
            ->count();

        $bounceRate = $sends > 0 ? $bounces / $sends : 0;
        $complaintRate = $sends > 0 ? $complaints / $sends : 0;

        return [
            'sends' => $sends,
            'bounces' => $bounces,
            'complaints' => $complaints,
            'bounce_rate' => $bounceRate,
            'complaint_rate' => $complaintRate,
        ];
    }

    private function suspendBrand(Brand $brand, array $metrics, SesDedicatedIpService $service): void
    {
        $service->suspendDedicatedIp($brand, $metrics);

        // Notify the brand owner
        $owner = $brand->account->users()->first();
        if ($owner) {
            try {
                Notification::send($owner, new DedicatedIpSuspended($brand, $metrics));
            } catch (\Exception $e) {
                Log::error('Failed to send suspension notification', [
                    'brand_id' => $brand->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->warn("Brand {$brand->id}: Dedicated IP suspended due to reputation issues");
    }
}
