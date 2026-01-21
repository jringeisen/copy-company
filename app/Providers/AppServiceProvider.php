<?php

namespace App\Providers;

use App\Listeners\HandleProSubscription;
use App\Listeners\HandleSubscriptionDowngrade;
use App\Models\Account;
use App\Services\Newsletter\BuiltInNewsletterService;
use App\Services\Newsletter\NewsletterServiceInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
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
        // Configure Cashier to use Account as the billable model
        Cashier::useCustomerModel(Account::class);

        // Register Socialite community providers
        // Note: Instagram uses Facebook OAuth (Instagram Business API requires Facebook auth)
        Event::listen(SocialiteWasCalled::class, PinterestExtendSocialite::class.'@handle');
        Event::listen(SocialiteWasCalled::class, TikTokExtendSocialite::class.'@handle');

        // Rate limiter for SES email sending (10 emails per second)
        RateLimiter::for('ses-sending', function (object $job) {
            return Limit::perSecond(10);
        });

        // Handle dedicated IP provisioning/release on subscription changes
        Event::listen(WebhookReceived::class, HandleProSubscription::class);
        Event::listen(WebhookReceived::class, HandleSubscriptionDowngrade::class);
    }
}
