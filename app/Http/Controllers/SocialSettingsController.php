<?php

namespace App\Http\Controllers;

use App\Enums\SocialPlatform;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Services\SocialPublishing\FacebookPagesService;
use App\Services\SocialPublishing\PinterestBoardsService;
use App\Services\SocialPublishing\PublisherFactory;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialSettingsController extends Controller
{
    use HasBrandAuthorization;

    public function __construct(
        protected TokenManager $tokenManager,
        protected FacebookPagesService $facebookPagesService,
        protected PinterestBoardsService $pinterestBoardsService
    ) {}

    /**
     * Get scopes for a platform from config.
     *
     * @return array<string>
     */
    protected function getScopesForPlatform(string $platform): array
    {
        $scopes = config("services.{$platform}.scopes", '');

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
                } elseif ($identifier === 'pinterest' && empty($credentials['board_id'])) {
                    $needsConfiguration = true;
                } elseif ($identifier === 'facebook' && ! empty($credentials['page_name'])) {
                    $configuredAccount = $credentials['page_name'];
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

    public function redirect(string $platform): RedirectResponse
    {
        if (! $this->isValidPlatform($platform)) {
            return back()->with('error', 'Invalid platform.');
        }

        $scopes = $this->getScopesForPlatform($platform);

        try {
            $driver = Socialite::driver($platform);

            // Add scopes
            if (! empty($scopes)) {
                $driver->scopes($scopes);
            }

            // Twitter uses OAuth 2.0 with PKCE
            if ($platform === 'twitter') {
                $driver->with(['response_type' => 'code']);
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
            $socialUser = Socialite::driver($platform)->user();

            $credentials = $this->buildCredentials($platform, $socialUser);

            $this->tokenManager->storeCredentials($brand, $platform, $credentials);

            // Redirect to account selection for platforms that require it
            if (in_array($platform, ['facebook', 'pinterest'])) {
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
        if (! in_array($platform, ['facebook', 'pinterest'])) {
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

        if ($platform === 'facebook') {
            $accounts = $this->facebookPagesService->fetchUserPages($credentials['access_token']);
            $accountType = 'page';
        } elseif ($platform === 'pinterest') {
            $accounts = $this->pinterestBoardsService->fetchUserBoards($credentials['access_token']);
            $accountType = 'board';
        }

        if (empty($accounts)) {
            return redirect()->route('settings.social')
                ->with('error', "No {$accountType}s found. Please create a {$accountType} on {$platformName} first.");
        }

        return Inertia::render('Settings/SocialAccountSelect', [
            'platform' => $platform,
            'platformName' => $platformName,
            'accountType' => $accountType,
            'accounts' => $accounts,
            'currentSelection' => $platform === 'facebook'
                ? ($credentials['page_id'] ?? null)
                : ($credentials['board_id'] ?? null),
        ]);
    }

    public function storeAccountSelection(Request $request, string $platform): RedirectResponse
    {
        if (! in_array($platform, ['facebook', 'pinterest'])) {
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
            'access_token' => 'nullable|string', // Only for Facebook pages
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
            'refresh_token' => $socialUser->refreshToken ?? null,
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
