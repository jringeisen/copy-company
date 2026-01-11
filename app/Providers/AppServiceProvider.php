<?php

namespace App\Providers;

use App\Services\Newsletter\BuiltInNewsletterService;
use App\Services\Newsletter\NewsletterServiceInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Pinterest\PinterestExtendSocialite;
use SocialiteProviders\TikTok\TikTokExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            NewsletterServiceInterface::class,
            BuiltInNewsletterService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Socialite community providers
        // Note: Instagram uses Facebook OAuth (Instagram Business API requires Facebook auth)
        Event::listen(SocialiteWasCalled::class, PinterestExtendSocialite::class.'@handle');
        Event::listen(SocialiteWasCalled::class, TikTokExtendSocialite::class.'@handle');

        // Rate limiter for SES email sending (10 emails per second)
        RateLimiter::for('ses-sending', function (object $job) {
            return Limit::perSecond(10);
        });
    }
}
