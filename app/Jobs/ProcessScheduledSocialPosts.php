<?php

namespace App\Jobs;

use App\Enums\SocialPostStatus;
use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledSocialPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $posts = SocialPost::query()
            ->where('status', SocialPostStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->with('brand')
            ->get();

        if ($posts->isEmpty()) {
            return;
        }

        Log::info('Processing scheduled social posts', [
            'count' => $posts->count(),
        ]);

        foreach ($posts as $post) {
            PublishSocialPost::dispatch($post);
        }
    }
}
