<?php

use App\Enums\NewsletterSendStatus;
use App\Enums\PostStatus;
use App\Enums\SocialPostStatus;
use App\Jobs\ProcessScheduledLoops;
use App\Jobs\ProcessScheduledSocialPosts;
use App\Models\Loop;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\SocialPost;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run scheduled posts publisher every minute
Schedule::command('posts:publish-scheduled')
    ->everyMinute()
    ->when(fn () => Post::where('status', PostStatus::Scheduled)
        ->where('scheduled_at', '<=', now())
        ->exists());

// Process scheduled newsletters every minute
Schedule::command('newsletters:process-scheduled')
    ->everyMinute()
    ->when(fn () => NewsletterSend::where('status', NewsletterSendStatus::Scheduled)
        ->where('scheduled_at', '<=', now())
        ->exists());

// Process scheduled social posts every minute
Schedule::job(new ProcessScheduledSocialPosts)
    ->everyMinute()
    ->when(fn () => SocialPost::where('status', SocialPostStatus::Scheduled)
        ->where('scheduled_at', '<=', now())
        ->exists());

// Process scheduled loops every minute
Schedule::job(new ProcessScheduledLoops)
    ->everyMinute()
    ->when(fn () => Loop::where('is_active', true)
        ->whereHas('schedules')
        ->whereHas('items')
        ->exists());

// Check SES reputation hourly
Schedule::command('ses:check-reputation')->hourly();

// Report email usage to Stripe for metered billing (hourly)
Schedule::command('billing:report-email-usage')->hourly();

// Check dedicated IP reputation hourly
Schedule::command('dedicated-ip:check-reputation')->hourly();
