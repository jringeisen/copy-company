<?php

namespace App\Jobs;

use App\Enums\SocialPostStatus;
use App\Models\Loop;
use App\Models\LoopItem;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledLoops implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Get all active loops with schedules and items
        $loops = Loop::query()
            ->where('is_active', true)
            ->with(['schedules', 'brand', 'items'])
            ->whereHas('items')
            ->whereHas('schedules')
            ->get();

        if ($loops->isEmpty()) {
            return;
        }

        foreach ($loops as $loop) {
            $this->processLoop($loop);
        }
    }

    protected function processLoop(Loop $loop): void
    {
        $brand = $loop->brand;
        $timezone = $brand->timezone ?? 'America/New_York';
        $now = Carbon::now($timezone);
        $currentDayOfWeek = $now->dayOfWeek;
        $currentTime = $now->format('H:i');

        foreach ($loop->schedules as $schedule) {
            // Check if this schedule matches current day and time (within 1 minute window)
            if ($schedule->day_of_week->value !== $currentDayOfWeek) {
                continue;
            }

            $scheduleTime = Carbon::parse($schedule->time_of_day, $timezone)->format('H:i');

            if ($scheduleTime !== $currentTime) {
                continue;
            }

            // Get the next item to post
            $item = $loop->getNextItem();

            if (! $item) {
                Log::warning('Loop has no items to post', ['loop_id' => $loop->id]);

                continue;
            }

            // Determine which platforms to post to
            $platforms = $schedule->platform
                ? [$schedule->platform->value]
                : $loop->platforms;

            foreach ($platforms as $platform) {
                $this->createAndPublishSocialPost($loop, $item, $platform);
            }

            // Advance the loop position
            $loop->advancePosition();

            // Record that this item was posted
            $item->recordPosted();

            Log::info('Loop post published', [
                'loop_id' => $loop->id,
                'item_id' => $item->id,
                'platforms' => $platforms,
            ]);
        }
    }

    protected function createAndPublishSocialPost(Loop $loop, LoopItem $item, string $platform): void
    {
        // Create a new SocialPost for this loop item
        $socialPost = SocialPost::create([
            'brand_id' => $loop->brand_id,
            'platform' => $platform,
            'format' => $item->getPostFormat()->value,
            'content' => $item->getPostContent(),
            'hashtags' => $item->getPostHashtags(),
            'link' => $item->getPostLink(),
            'media' => $item->getPostMedia(),
            'status' => SocialPostStatus::Queued,
            'ai_generated' => false,
            'user_edited' => false,
        ]);

        // Dispatch the publish job
        PublishSocialPost::dispatch($socialPost);
    }
}
