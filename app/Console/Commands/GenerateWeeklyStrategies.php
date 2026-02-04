<?php

namespace App\Console\Commands;

use App\Enums\MarketingStrategyStatus;
use App\Jobs\GenerateMarketingStrategy;
use App\Models\Brand;
use App\Models\MarketingStrategy;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklyStrategies extends Command
{
    protected $signature = 'strategies:generate-weekly';

    protected $description = 'Generate weekly marketing strategies for all qualifying brands';

    public function handle(): int
    {
        $weekStart = Carbon::now()->next(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek();

        $this->info("Generating strategies for week: {$weekStart->toDateString()} to {$weekEnd->toDateString()}");

        $brands = Brand::with('account')->get();

        $created = 0;
        $skipped = 0;

        foreach ($brands as $brand) {
            $account = $brand->account;

            if (! $account) {
                $skipped++;

                continue;
            }

            $limits = $account->subscriptionLimits();

            if (! $limits->canUseMarketingStrategy()) {
                $skipped++;

                continue;
            }

            $exists = MarketingStrategy::where('brand_id', $brand->id)
                ->where('week_start', $weekStart->toDateString())
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $strategy = MarketingStrategy::create([
                'brand_id' => $brand->id,
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'status' => MarketingStrategyStatus::Pending,
            ]);

            GenerateMarketingStrategy::dispatch($strategy);
            $created++;
        }

        $this->info("Created {$created} strategies, skipped {$skipped} brands.");

        return self::SUCCESS;
    }
}
