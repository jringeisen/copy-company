<?php

namespace App\Services\Newsletter;

use App\Enums\NewsletterSendStatus;
use App\Jobs\SendNewsletterToSubscriber;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;

class BuiltInNewsletterService implements NewsletterServiceInterface
{
    public function send(Brand $brand, Post $post, string $subjectLine, ?string $previewText = null): NewsletterSend
    {
        $subscribers = $brand->subscribers()
            ->confirmed()
            ->get();

        $newsletterSend = NewsletterSend::create([
            'brand_id' => $brand->id,
            'post_id' => $post->id,
            'subject' => $subjectLine,
            'preview_text' => $previewText,
            'status' => NewsletterSendStatus::Sending,
            'total_recipients' => $subscribers->count(),
            'sent_count' => 0,
            'failed_count' => 0,
            'sent_at' => now(),
        ]);

        // Dispatch jobs for each subscriber
        foreach ($subscribers as $subscriber) {
            SendNewsletterToSubscriber::dispatch($newsletterSend, $subscriber);
        }

        return $newsletterSend;
    }

    public function getSubscriberCount(Brand $brand): int
    {
        return $brand->subscribers()
            ->confirmed()
            ->count();
    }
}
