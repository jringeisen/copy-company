<?php

namespace App\Jobs;

use App\Enums\ContentSprintStatus;
use App\Models\ContentSprint;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateContentSprint implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public ContentSprint $sprint
    ) {}

    public function handle(AIService $aiService): void
    {
        $this->sprint->update(['status' => ContentSprintStatus::Generating]);

        try {
            $ideas = $aiService->generateContentSprintIdeas(
                $this->sprint->brand,
                $this->sprint->inputs['topics'] ?? [],
                $this->sprint->inputs['goals'] ?? '',
                $this->sprint->inputs['content_count'] ?? 20
            );

            $this->sprint->update([
                'generated_content' => $ideas,
                'status' => ContentSprintStatus::Completed,
                'completed_at' => now(),
            ]);

            Log::info('Content sprint completed', [
                'sprint_id' => $this->sprint->id,
                'ideas_count' => count($ideas),
            ]);
        } catch (\Exception $e) {
            Log::error('Content sprint generation failed', [
                'sprint_id' => $this->sprint->id,
                'error' => $e->getMessage(),
            ]);

            // Set failed immediately so users see the error state.
            // On retry, handle() will reset to Generating.
            $this->sprint->update(['status' => ContentSprintStatus::Failed]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->sprint->update(['status' => ContentSprintStatus::Failed]);

        Log::error('Content sprint job failed permanently', [
            'sprint_id' => $this->sprint->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
