<?php

namespace App\Console\Commands;

use App\Enums\DedicatedIpStatus;
use App\Models\Brand;
use App\Models\DedicatedIpLog;
use App\Services\SesDedicatedIpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDedicatedIpWarmup extends Command
{
    protected $signature = 'dedicated-ip:process-warmup';

    protected $description = 'Process daily warmup progression for dedicated IPs';

    public function handle(SesDedicatedIpService $service): int
    {
        $brands = Brand::where('dedicated_ip_status', DedicatedIpStatus::Warming)->get();

        $this->info("Processing warmup for {$brands->count()} brands...");

        foreach ($brands as $brand) {
            $this->processWarmup($brand, $service);
        }

        $this->info('Warmup processing complete.');

        return Command::SUCCESS;
    }

    private function processWarmup(Brand $brand, SesDedicatedIpService $service): void
    {
        $inactivityDays = config('services.ses.warmup_inactivity_days', 7);

        // Check for inactivity
        if ($brand->last_warmup_send_at?->diffInDays(now()) >= $inactivityDays) {
            if (! $brand->warmup_paused) {
                $brand->update(['warmup_paused' => true]);

                DedicatedIpLog::create([
                    'brand_id' => $brand->id,
                    'action' => 'warmup_paused',
                    'ip_address' => $brand->dedicated_ip_address,
                    'metadata' => ['reason' => 'no_sends_7_days'],
                ]);

                $this->warn("Brand {$brand->id}: Warmup paused due to inactivity");
                Log::info('Warmup paused due to inactivity', ['brand_id' => $brand->id]);
            }

            return;
        }

        // Resume if was paused and now sending again
        if ($brand->warmup_paused && $brand->last_warmup_send_at?->diffInDays(now()) < $inactivityDays) {
            $brand->update(['warmup_paused' => false]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'warmup_resumed',
                'ip_address' => $brand->dedicated_ip_address,
            ]);

            $this->info("Brand {$brand->id}: Warmup resumed");
            Log::info('Warmup resumed', ['brand_id' => $brand->id]);
        }

        // Skip if paused
        if ($brand->warmup_paused) {
            return;
        }

        $currentDay = $brand->warmup_day ?? 1;
        $newDay = min($currentDay + 1, 20);

        // Check if warmup should be complete
        if ($newDay >= 20) {
            // Check AWS warmup status
            $status = $service->getWarmupStatus($brand);
            if ($status['success'] && ($status['data']['warmup_status'] ?? '') === 'DONE') {
                $service->completeWarmup($brand);
                $this->info("Brand {$brand->id}: Warmup completed!");

                return;
            }
        }

        // Increment warmup day
        $brand->update(['warmup_day' => $newDay]);

        DedicatedIpLog::create([
            'brand_id' => $brand->id,
            'action' => 'warmup_day_incremented',
            'ip_address' => $brand->dedicated_ip_address,
            'metadata' => [
                'previous_day' => $currentDay,
                'new_day' => $newDay,
            ],
        ]);

        $this->info("Brand {$brand->id}: Warmup day {$currentDay} -> {$newDay}");
        Log::info('Warmup day incremented', [
            'brand_id' => $brand->id,
            'new_day' => $newDay,
        ]);
    }
}
