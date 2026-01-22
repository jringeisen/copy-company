<?php

namespace App\Providers;

use App\Listeners\StoreOAuthTokenContext;
use App\Models\Account;
use App\Services\Newsletter\BuiltInNewsletterService;
use App\Services\Newsletter\NewsletterServiceInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;
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

        // Configure Passport for OAuth 2.1 MCP authentication
        Passport::authorizationView(function ($parameters) {
            // Add user's brands to the view for brand selection
            $user = $parameters['user'];
            $account = $user->currentAccount();
            $parameters['brands'] = $account?->brands()->get() ?? collect();

            return view('mcp.authorize', $parameters);
        });

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        // Register Passport event listener for storing brand context with OAuth tokens
        Event::listen(AccessTokenCreated::class, StoreOAuthTokenContext::class);

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
