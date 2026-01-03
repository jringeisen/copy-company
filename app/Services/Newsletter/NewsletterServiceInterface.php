<?php

namespace App\Services\Newsletter;

use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;

interface NewsletterServiceInterface
{
    /**
     * Send a newsletter to all confirmed subscribers of a brand
     */
    public function send(Brand $brand, Post $post, string $subjectLine, ?string $previewText = null): NewsletterSend;

    /**
     * Get the number of confirmed subscribers for a brand
     */
    public function getSubscriberCount(Brand $brand): int;
}
