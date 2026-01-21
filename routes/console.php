<?php

use App\Jobs\ProcessScheduledLoops;
use App\Jobs\ProcessScheduledSocialPosts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run scheduled posts publisher every minute
Schedule::command('posts:publish-scheduled')->everyMinute();

// Process scheduled newsletters every minute
Schedule::command('newsletters:process-scheduled')->everyMinute();

// Process scheduled social posts every minute
Schedule::job(new ProcessScheduledSocialPosts)->everyMinute();

// Process scheduled loops every minute
Schedule::job(new ProcessScheduledLoops)->everyMinute();

// Check SES reputation hourly
Schedule::command('ses:check-reputation')->hourly();

// Report email usage to Stripe for metered billing (hourly)
Schedule::command('billing:report-email-usage')->hourly();

// Process dedicated IP warmup daily at 00:05
Schedule::command('dedicated-ip:process-warmup')->dailyAt('00:05');

// Check dedicated IP reputation hourly
Schedule::command('dedicated-ip:check-reputation')->hourly();

// Check dedicated IP pool availability daily at 09:00
Schedule::command('dedicated-ip:check-pool')->dailyAt('09:00');
