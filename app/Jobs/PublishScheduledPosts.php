<?php

namespace App\Jobs;

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
        $posts = Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($posts as $post) {
            try {
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                // Dispatch newsletter if configured
                if ($post->send_as_newsletter) {
                    $newsletterSend = $post->newsletterSend;

                    if ($newsletterSend && $newsletterSend->status === 'pending') {
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
        $brand = $newsletterSend->post->brand;
        $subscribers = $brand->subscribers()
            ->where('status', 'confirmed')
            ->get();

        if ($subscribers->isEmpty()) {
            $newsletterSend->update(['status' => 'sent']);

            return;
        }

        $newsletterSend->update([
            'status' => 'sending',
            'total_recipients' => $subscribers->count(),
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        foreach ($subscribers as $subscriber) {
            SendNewsletterToSubscriber::dispatch($newsletterSend, $subscriber);
        }
    }
}
