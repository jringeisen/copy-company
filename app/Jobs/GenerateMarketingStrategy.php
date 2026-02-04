<?php

namespace App\Jobs;

use App\Enums\MarketingStrategyStatus;
use App\Models\MarketingStrategy;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMarketingStrategy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public MarketingStrategy $strategy
    ) {}

    public function handle(AIService $aiService): void
    {
        $this->strategy->update(['status' => MarketingStrategyStatus::Generating]);

        try {
            $result = $aiService->generateMarketingStrategy($this->strategy->brand);

            $this->strategy->update([
                'strategy_content' => $result['strategy'],
                'context_snapshot' => $result['context'],
                'status' => MarketingStrategyStatus::Completed,
                'completed_at' => now(),
            ]);

            Log::info('Marketing strategy completed', [
                'strategy_id' => $this->strategy->id,
                'brand_id' => $this->strategy->brand_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Marketing strategy generation failed', [
                'strategy_id' => $this->strategy->id,
                'error' => $e->getMessage(),
            ]);

            $this->strategy->update(['status' => MarketingStrategyStatus::Failed]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->strategy->update(['status' => MarketingStrategyStatus::Failed]);

        Log::error('Marketing strategy job failed permanently', [
            'strategy_id' => $this->strategy->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
