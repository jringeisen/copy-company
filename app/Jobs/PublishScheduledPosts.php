<?php

namespace App\Jobs;

use App\Enums\NewsletterSendStatus;
use App\Enums\PostStatus;
use App\Models\NewsletterSend;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishScheduledPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        // Eager load relationships to avoid N+1 queries
        $posts = Post::where('status', PostStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->with(['newsletterSend', 'brand'])
            ->get();

        foreach ($posts as $post) {
            try {
                $post->update([
                    'status' => PostStatus::Published,
                    'published_at' => now(),
                ]);

                // Dispatch newsletter if configured
                if ($post->send_as_newsletter) {
                    $newsletterSend = $post->newsletterSend;

                    if ($newsletterSend && $newsletterSend->status === NewsletterSendStatus::Draft) {
                        $this->dispatchNewsletter($newsletterSend);
                    }
                }

                Log::info('Scheduled post published', [
                    'post_id' => $post->id,
                    'title' => $post->title,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to publish scheduled post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function dispatchNewsletter(NewsletterSend $newsletterSend): void
    {
        // Eager load if not already loaded
        $newsletterSend->loadMissing('post.brand');
        $brand = $newsletterSend->post->brand;

        $subscriberCount = $brand->subscribers()->confirmed()->count();

        if ($subscriberCount === 0) {
            $newsletterSend->update(['status' => NewsletterSendStatus::Sent]);

            return;
        }

        $newsletterSend->update([
            'status' => NewsletterSendStatus::Sending,
            'total_recipients' => $subscriberCount,
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        // Use chunkById to avoid loading all subscribers into memory
        $brand->subscribers()
            ->confirmed()
            ->chunkById(100, function (\Illuminate\Database\Eloquent\Collection $subscribers) use ($newsletterSend): void {
                foreach ($subscribers as $subscriber) {
                    SendNewsletterToSubscriber::dispatch($newsletterSend, $subscriber);
                }
            });
    }
}
