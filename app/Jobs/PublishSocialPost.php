<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\SocialPublishing\SocialPublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishSocialPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        public SocialPost $socialPost
    ) {}

    public function handle(SocialPublishingService $service): void
    {
        Log::info('Publishing social post', [
            'social_post_id' => $this->socialPost->id,
            'platform' => $this->socialPost->platform->value,
        ]);

        $service->publishAndUpdateStatus($this->socialPost);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Social post publishing failed permanently', [
            'social_post_id' => $this->socialPost->id,
            'platform' => $this->socialPost->platform->value,
            'error' => $exception->getMessage(),
        ]);
    }
}
