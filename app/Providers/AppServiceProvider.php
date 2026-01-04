<?php

namespace App\Providers;

use App\Services\Newsletter\BuiltInNewsletterService;
use App\Services\Newsletter\NewsletterServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Instagram\InstagramExtendSocialite;
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
        Event::listen(SocialiteWasCalled::class, InstagramExtendSocialite::class.'@handle');
        Event::listen(SocialiteWasCalled::class, PinterestExtendSocialite::class.'@handle');
        Event::listen(SocialiteWasCalled::class, TikTokExtendSocialite::class.'@handle');
    }
}
