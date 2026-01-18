<?php

namespace App\Http\Controllers;

use App\Enums\SocialPlatform;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Services\SocialPublishing\FacebookPagesService;
use App\Services\SocialPublishing\InstagramAccountsService;
use App\Services\SocialPublishing\PinterestBoardsService;
use App\Services\SocialPublishing\PublisherFactory;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialSettingsController extends Controller
{
    use HasBrandAuthorization;

    public function __construct(
        protected TokenManager $tokenManager,
        protected FacebookPagesService $facebookPagesService,
        protected InstagramAccountsService $instagramAccountsService,
        protected PinterestBoardsService $pinterestBoardsService
    ) {}

    /**
     * Get scopes for a platform from config.
     *
     * @return array<string>
     */
    protected function getScopesForPlatform(string $platform): array
    {
        // LinkedIn uses 'linkedin-openid' as the config key
        $configKey = $platform === 'linkedin' ? 'linkedin-openid' : $platform;
        $scopes = config("services.{$configKey}.scopes", '');

        if (empty($scopes)) {
            return [];
        }

        return array_map('trim', explode(',', $scopes));
    }

    public function index(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $platforms = collect(SocialPlatform::cases())->map(function (SocialPlatform $platform) use ($brand): array {
            $identifier = $platform->value;
            $isSupported = PublisherFactory::isSupported($identifier);
            $connectionInfo = $this->tokenManager->getConnectionInfo($brand, $identifier);
            $credentials = $this->tokenManager->getCredentials($brand, $identifier);

            // Check if platform needs additional configuration
            $needsConfiguration = false;
            $configuredAccount = null;

            if ($connectionInfo !== null) {
                if ($identifier === 'facebook' && empty($credentials['page_id'])) {
                    $needsConfiguration = true;
                } elseif ($identifier === 'instagram' && empty($credentials['instagram_account_id'])) {
                    $needsConfiguration = true;
                } elseif ($identifier === 'pinterest' && empty($credentials['board_id'])) {
                    $needsConfiguration = true;
                } elseif ($identifier === 'facebook' && ! empty($credentials['page_name'])) {
                    $configuredAccount = $credentials['page_name'];
                } elseif ($identifier === 'instagram' && ! empty($credentials['instagram_username'])) {
                    $configuredAccount = '@'.$credentials['instagram_username'];
                } elseif ($identifier === 'pinterest' && ! empty($credentials['board_name'])) {
                    $configuredAccount = $credentials['board_name'];
                }
            }

            return [
                'identifier' => $identifier,
                'name' => $platform->displayName(),
                'connected' => $connectionInfo !== null,
                'supported' => $isSupported,
                'account_name' => $connectionInfo['account_name'] ?? null,
                'connected_at' => $connectionInfo['connected_at'] ?? null,
                'needs_configuration' => $needsConfiguration,
                'configured_account' => $configuredAccount,
            ];
        });

        return Inertia::render('Settings/Social', [
            'platforms' => $platforms,
            'brand' => $brand,
        ]);
    }

    public function redirect(string $platform): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (! $this->isValidPlatform($platform)) {
            return back()->with('error', 'Invalid platform.');
        }

        $scopes = $this->getScopesForPlatform($platform);

        try {
            // Instagram Business API uses Facebook OAuth
            // LinkedIn uses OpenID Connect (linkedin-openid driver)
            $driverName = match ($platform) {
                'instagram' => 'facebook',
                'linkedin' => 'linkedin-openid',
                default => $platform,
            };

            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($driverName);

            // Add scopes
            if (! empty($scopes)) {
                $driver->scopes($scopes);
            }

            // For Instagram, we need to use the Instagram redirect URI
            if ($platform === 'instagram') {
                $driver->redirectUrl(url(config('services.instagram.redirect')));
            }

            return $driver->redirect();
        } catch (\Exception $e) {
            Log::error("Socialite redirect failed for {$platform}", [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to connect to '.$platform.'. Please check your API credentials.');
        }
    }

    public function callback(string $platform): RedirectResponse
    {
        if (! $this->isValidPlatform($platform)) {
            return redirect()->route('settings.social')->with('error', 'Invalid platform.');
        }

        $brand = $this->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        try {
            // Instagram Business API uses Facebook OAuth
            // LinkedIn uses OpenID Connect (linkedin-openid driver)
            $driverName = match ($platform) {
                'instagram' => 'facebook',
                'linkedin' => 'linkedin-openid',
                default => $platform,
            };

            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($driverName);

            // For Instagram, set the correct redirect URL for the callback
            if ($platform === 'instagram') {
                $driver->redirectUrl(url(config('services.instagram.redirect')));
            }

            /** @var SocialiteUser $socialUser */
            $socialUser = $driver->user();

            $credentials = $this->buildCredentials($platform, $socialUser);

            $this->tokenManager->storeCredentials($brand, $platform, $credentials);

            // Redirect to account selection for platforms that require it
            if (in_array($platform, ['facebook', 'instagram', 'pinterest'])) {
                return redirect()->route('settings.social.select', ['platform' => $platform])
                    ->with('info', 'Please select which account to publish to.');
            }

            return redirect()->route('settings.social')
                ->with('success', ucfirst($platform).' connected successfully!');
        } catch (\Exception $e) {
            Log::error("Socialite callback failed for {$platform}", [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('settings.social')
                ->with('error', 'Failed to connect '.$platform.': '.$e->getMessage());
        }
    }

    public function disconnect(string $platform): RedirectResponse
    {
        if (! $this->isValidPlatform($platform)) {
            return back()->with('error', 'Invalid platform.');
        }

        $brand = $this->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        $this->authorize('update', $brand);

        $this->tokenManager->removeCredentials($brand, $platform);

        return back()->with('success', ucfirst($platform).' disconnected.');
    }

    public function showAccountSelection(string $platform): Response|RedirectResponse
    {
        if (! in_array($platform, ['facebook', 'instagram', 'pinterest'])) {
            return redirect()->route('settings.social')->with('error', 'Account selection is not required for this platform.');
        }

        $brand = $this->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        $credentials = $this->tokenManager->getCredentials($brand, $platform);

        if (! $credentials) {
            return redirect()->route('settings.social')->with('error', 'Please connect your '.$platform.' account first.');
        }

        $accounts = [];
        $platformName = ucfirst($platform);
        $accountType = '';
        $currentSelection = null;

        if ($platform === 'facebook') {
            $accounts = $this->facebookPagesService->fetchUserPages($credentials['access_token']);
            $accountType = 'page';
            $currentSelection = $credentials['page_id'] ?? null;
        } elseif ($platform === 'instagram') {
            $accounts = $this->instagramAccountsService->fetchInstagramAccounts($credentials['access_token']);
            $accountType = 'account';
            $currentSelection = $credentials['instagram_account_id'] ?? null;
        } elseif ($platform === 'pinterest') {
            $accounts = $this->pinterestBoardsService->fetchUserBoards($credentials['access_token']);
            $accountType = 'board';
            $currentSelection = $credentials['board_id'] ?? null;
        }

        if (empty($accounts)) {
            $errorMessage = $platform === 'instagram'
                ? 'No Instagram Business accounts found. Please make sure you have an Instagram Business or Creator account connected to a Facebook Page.'
                : "No {$accountType}s found. Please create a {$accountType} on {$platformName} first.";

            return redirect()->route('settings.social')->with('error', $errorMessage);
        }

        return Inertia::render('Settings/SocialAccountSelect', [
            'platform' => $platform,
            'platformName' => $platformName,
            'accountType' => $accountType,
            'accounts' => $accounts,
            'currentSelection' => $currentSelection,
        ]);
    }

    public function storeAccountSelection(Request $request, string $platform): RedirectResponse
    {
        if (! in_array($platform, ['facebook', 'instagram', 'pinterest'])) {
            return redirect()->route('settings.social')->with('error', 'Invalid platform.');
        }

        $brand = $this->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        $this->authorize('update', $brand);

        $validated = $request->validate([
            'account_id' => 'required|string',
            'account_name' => 'required|string',
            'access_token' => 'nullable|string', // For Facebook pages and Instagram
        ]);

        $credentials = $this->tokenManager->getCredentials($brand, $platform);

        if (! $credentials) {
            return redirect()->route('settings.social')->with('error', 'Please connect your account first.');
        }

        if ($platform === 'facebook') {
            $credentials['page_id'] = $validated['account_id'];
            $credentials['page_name'] = $validated['account_name'];
            if (! empty($validated['access_token'])) {
                $credentials['page_access_token'] = $validated['access_token'];
            }
        } elseif ($platform === 'instagram') {
            $credentials['instagram_account_id'] = $validated['account_id'];
            $credentials['instagram_username'] = $validated['account_name'];
            // Use the page access token for Instagram API calls
            if (! empty($validated['access_token'])) {
                $credentials['access_token'] = $validated['access_token'];
            }
        } elseif ($platform === 'pinterest') {
            $credentials['board_id'] = $validated['account_id'];
            $credentials['board_name'] = $validated['account_name'];
        }

        $this->tokenManager->storeCredentials($brand, $platform, $credentials);

        return redirect()->route('settings.social')
            ->with('success', ucfirst($platform).' configured successfully!');
    }

    protected function isValidPlatform(string $platform): bool
    {
        return SocialPlatform::tryFrom($platform) !== null;
    }

    /**
     * Build credentials array from Socialite user.
     *
     * @return array<string, mixed>
     */
    protected function buildCredentials(string $platform, SocialiteUser $socialUser): array
    {
        $credentials = [
            'access_token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken ?: null,
            'expires_at' => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)->toDateTimeString()
                : null,
            'account_id' => $socialUser->getId(),
            'account_name' => $socialUser->getNickname() ?? $socialUser->getName(),
        ];

        // Platform-specific fields
        switch ($platform) {
            case 'facebook':
                // For Facebook, we need to get page access tokens
                $credentials['user_id'] = $socialUser->getId();
                break;

            case 'instagram':
                // Instagram uses Facebook's Graph API
                $credentials['instagram_account_id'] = $socialUser->getId();
                break;

            case 'linkedin':
                $credentials['person_id'] = $socialUser->getId();
                break;

            case 'pinterest':
                // Pinterest may require a default board ID
                $credentials['user_id'] = $socialUser->getId();
                break;
        }

        return $credentials;
    }
}
