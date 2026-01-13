<?php

namespace Tests\Feature\Controllers;

use App\Models\Account;
use App\Models\Brand;
use App\Models\User;
use App\Services\SocialPublishing\FacebookPagesService;
use App\Services\SocialPublishing\PinterestBoardsService;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SocialSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Brand $brand;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles needed for brand operations
        Permission::findOrCreate('brands.update', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(['brands.update']);

        $this->user = User::factory()->create();
        $this->account = Account::factory()->create();
        $this->account->users()->attach($this->user->id, ['role' => 'admin']);
        $this->brand = Brand::factory()->forAccount($this->account)->create();

        // Set up permissions team context and assign role
        setPermissionsTeamId($this->account->id);
        $this->user->assignRole('admin');
    }

    public function test_guests_cannot_access_social_settings(): void
    {
        $response = $this->get('/settings/social');

        $response->assertRedirect('/login');
    }

    public function test_users_with_brand_can_view_social_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/Social')
            ->has('platforms')
            ->has('brand')
        );
    }

    public function test_social_settings_shows_all_platforms(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', 5)
            ->where('platforms.0.identifier', 'instagram')
            ->where('platforms.0.connected', false)
        );
    }

    public function test_social_settings_shows_connected_platforms(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'linkedin', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', fn ($platforms) => $platforms
                ->where('3.identifier', 'linkedin')
                ->where('3.connected', true)
                ->where('3.account_name', 'Test User')
                ->etc()
            )
        );
    }

    public function test_redirect_returns_error_for_invalid_platform(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/invalid/redirect');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_disconnect_removes_platform_credentials(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'linkedin', [
            'access_token' => 'test_token',
        ]);

        $this->assertTrue($tokenManager->isConnected($this->brand, 'linkedin'));

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->delete('/settings/social/linkedin');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->brand->refresh();
        $this->assertFalse($tokenManager->isConnected($this->brand, 'linkedin'));
    }

    public function test_disconnect_returns_error_for_invalid_platform(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->delete('/settings/social/invalid');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_users_without_brand_are_redirected_to_brand_create(): void
    {
        $userWithoutBrand = User::factory()->create();
        $accountWithoutBrand = Account::factory()->create();
        $accountWithoutBrand->users()->attach($userWithoutBrand->id, ['role' => 'admin']);

        $response = $this->actingAs($userWithoutBrand)
            ->withSession(['current_account_id' => $accountWithoutBrand->id])
            ->get('/settings/social');

        $response->assertRedirect(route('brands.create'));
    }

    public function test_account_selection_redirects_for_non_supported_platform(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/linkedin/select');

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('error');
    }

    public function test_account_selection_requires_connected_platform(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/facebook/select');

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('error', 'Please connect your facebook account first.');
    }

    public function test_facebook_account_selection_shows_pages(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
        ]);

        $mockPagesService = Mockery::mock(FacebookPagesService::class);
        $mockPagesService->shouldReceive('fetchUserPages')
            ->with('test_token')
            ->once()
            ->andReturn([
                ['id' => '123', 'name' => 'My Page', 'access_token' => 'page_token_123'],
                ['id' => '456', 'name' => 'Other Page', 'access_token' => 'page_token_456'],
            ]);
        $this->app->instance(FacebookPagesService::class, $mockPagesService);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/facebook/select');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/SocialAccountSelect')
            ->where('platform', 'facebook')
            ->where('platformName', 'Facebook')
            ->where('accountType', 'page')
            ->has('accounts', 2)
            ->where('accounts.0.id', '123')
            ->where('accounts.0.name', 'My Page')
        );
    }

    public function test_facebook_account_selection_redirects_when_no_pages(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
        ]);

        $mockPagesService = Mockery::mock(FacebookPagesService::class);
        $mockPagesService->shouldReceive('fetchUserPages')
            ->with('test_token')
            ->once()
            ->andReturn([]);
        $this->app->instance(FacebookPagesService::class, $mockPagesService);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/facebook/select');

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('error');
    }

    public function test_pinterest_account_selection_shows_boards(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'pinterest', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
        ]);

        $mockBoardsService = Mockery::mock(PinterestBoardsService::class);
        $mockBoardsService->shouldReceive('fetchUserBoards')
            ->with('test_token')
            ->once()
            ->andReturn([
                ['id' => 'board_123', 'name' => 'My Board'],
                ['id' => 'board_456', 'name' => 'Other Board'],
            ]);
        $this->app->instance(PinterestBoardsService::class, $mockBoardsService);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/pinterest/select');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/SocialAccountSelect')
            ->where('platform', 'pinterest')
            ->where('platformName', 'Pinterest')
            ->where('accountType', 'board')
            ->has('accounts', 2)
        );
    }

    public function test_store_facebook_account_selection(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'user_token',
            'account_name' => 'Test User',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->post('/settings/social/facebook/select', [
                'account_id' => '123',
                'account_name' => 'My Page',
                'access_token' => 'page_token_123',
            ]);

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('success');

        $credentials = $tokenManager->getCredentials($this->brand->fresh(), 'facebook');
        $this->assertEquals('123', $credentials['page_id']);
        $this->assertEquals('My Page', $credentials['page_name']);
        $this->assertEquals('page_token_123', $credentials['page_access_token']);
    }

    public function test_store_pinterest_account_selection(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'pinterest', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->post('/settings/social/pinterest/select', [
                'account_id' => 'board_123',
                'account_name' => 'My Board',
            ]);

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('success');

        $credentials = $tokenManager->getCredentials($this->brand->fresh(), 'pinterest');
        $this->assertEquals('board_123', $credentials['board_id']);
        $this->assertEquals('My Board', $credentials['board_name']);
    }

    public function test_social_settings_shows_needs_configuration_for_facebook_without_page(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', fn ($platforms) => $platforms
                ->where('1.identifier', 'facebook')
                ->where('1.connected', true)
                ->where('1.needs_configuration', true)
                ->etc()
            )
        );
    }

    public function test_social_settings_shows_configured_account_for_facebook_with_page(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
            'page_id' => '123',
            'page_name' => 'My Business Page',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', fn ($platforms) => $platforms
                ->where('1.identifier', 'facebook')
                ->where('1.connected', true)
                ->where('1.needs_configuration', false)
                ->where('1.configured_account', 'My Business Page')
                ->etc()
            )
        );
    }

    public function test_social_settings_shows_configured_account_for_instagram(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'instagram', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
            'instagram_account_id' => '123',
            'instagram_username' => 'my_instagram',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', fn ($platforms) => $platforms
                ->where('0.identifier', 'instagram')
                ->where('0.connected', true)
                ->where('0.needs_configuration', false)
                ->where('0.configured_account', '@my_instagram')
                ->etc()
            )
        );
    }

    public function test_social_settings_shows_needs_configuration_for_instagram_without_account(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'instagram', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', fn ($platforms) => $platforms
                ->where('0.identifier', 'instagram')
                ->where('0.connected', true)
                ->where('0.needs_configuration', true)
                ->etc()
            )
        );
    }

    public function test_social_settings_shows_configured_account_for_pinterest(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'pinterest', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
            'board_id' => '123',
            'board_name' => 'My Board',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', fn ($platforms) => $platforms
                ->where('2.identifier', 'pinterest')
                ->where('2.connected', true)
                ->where('2.needs_configuration', false)
                ->where('2.configured_account', 'My Board')
                ->etc()
            )
        );
    }

    public function test_social_settings_shows_needs_configuration_for_pinterest_without_board(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'pinterest', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', fn ($platforms) => $platforms
                ->where('2.identifier', 'pinterest')
                ->where('2.connected', true)
                ->where('2.needs_configuration', true)
                ->etc()
            )
        );
    }

    public function test_instagram_account_selection_shows_accounts(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'instagram', [
            'access_token' => 'test_token',
            'account_name' => 'Test User',
        ]);

        $mockInstagramService = Mockery::mock(\App\Services\SocialPublishing\InstagramAccountsService::class);
        $mockInstagramService->shouldReceive('fetchInstagramAccounts')
            ->with('test_token')
            ->once()
            ->andReturn([
                ['id' => '123', 'username' => 'my_instagram', 'name' => 'My Instagram'],
            ]);
        $this->app->instance(\App\Services\SocialPublishing\InstagramAccountsService::class, $mockInstagramService);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/instagram/select');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/SocialAccountSelect')
            ->where('platform', 'instagram')
            ->where('platformName', 'Instagram')
            ->where('accountType', 'account')
            ->has('accounts', 1)
        );
    }

    public function test_instagram_account_selection_shows_custom_error_when_no_accounts(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'instagram', [
            'access_token' => 'test_token',
        ]);

        $mockInstagramService = Mockery::mock(\App\Services\SocialPublishing\InstagramAccountsService::class);
        $mockInstagramService->shouldReceive('fetchInstagramAccounts')
            ->with('test_token')
            ->once()
            ->andReturn([]);
        $this->app->instance(\App\Services\SocialPublishing\InstagramAccountsService::class, $mockInstagramService);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/instagram/select');

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('error', 'No Instagram Business accounts found. Please make sure you have an Instagram Business or Creator account connected to a Facebook Page.');
    }

    public function test_store_instagram_account_selection(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'instagram', [
            'access_token' => 'user_token',
            'account_name' => 'Test User',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->post('/settings/social/instagram/select', [
                'account_id' => '123',
                'account_name' => 'my_instagram',
                'access_token' => 'page_token_for_instagram',
            ]);

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('success');

        $credentials = $tokenManager->getCredentials($this->brand->fresh(), 'instagram');
        $this->assertEquals('123', $credentials['instagram_account_id']);
        $this->assertEquals('my_instagram', $credentials['instagram_username']);
        $this->assertEquals('page_token_for_instagram', $credentials['access_token']);
    }

    public function test_store_account_selection_requires_credentials(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->post('/settings/social/facebook/select', [
                'account_id' => '123',
                'account_name' => 'My Page',
            ]);

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('error', 'Please connect your account first.');
    }

    public function test_store_account_selection_returns_error_for_invalid_platform(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->post('/settings/social/linkedin/select', [
                'account_id' => '123',
                'account_name' => 'Test',
            ]);

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('error', 'Invalid platform.');
    }

    public function test_disconnect_redirects_to_brand_create_if_no_brand(): void
    {
        $userWithoutBrand = User::factory()->create();
        $accountWithoutBrand = Account::factory()->create();
        $accountWithoutBrand->users()->attach($userWithoutBrand->id, ['role' => 'admin']);

        $response = $this->actingAs($userWithoutBrand)
            ->withSession(['current_account_id' => $accountWithoutBrand->id])
            ->delete('/settings/social/linkedin');

        $response->assertRedirect(route('brands.create'));
    }

    public function test_account_selection_redirects_to_brand_create_if_no_brand(): void
    {
        $userWithoutBrand = User::factory()->create();
        $accountWithoutBrand = Account::factory()->create();
        $accountWithoutBrand->users()->attach($userWithoutBrand->id, ['role' => 'admin']);

        $response = $this->actingAs($userWithoutBrand)
            ->withSession(['current_account_id' => $accountWithoutBrand->id])
            ->get('/settings/social/facebook/select');

        $response->assertRedirect(route('brands.create'));
    }

    public function test_store_account_selection_redirects_to_brand_create_if_no_brand(): void
    {
        $userWithoutBrand = User::factory()->create();
        $accountWithoutBrand = Account::factory()->create();
        $accountWithoutBrand->users()->attach($userWithoutBrand->id, ['role' => 'admin']);

        $response = $this->actingAs($userWithoutBrand)
            ->withSession(['current_account_id' => $accountWithoutBrand->id])
            ->post('/settings/social/facebook/select', [
                'account_id' => '123',
                'account_name' => 'Test',
            ]);

        $response->assertRedirect(route('brands.create'));
    }

    public function test_callback_redirects_to_brand_create_if_no_brand(): void
    {
        $userWithoutBrand = User::factory()->create();
        $accountWithoutBrand = Account::factory()->create();
        $accountWithoutBrand->users()->attach($userWithoutBrand->id, ['role' => 'admin']);

        $response = $this->actingAs($userWithoutBrand)
            ->withSession(['current_account_id' => $accountWithoutBrand->id])
            ->get('/settings/social/linkedin/callback');

        $response->assertRedirect(route('brands.create'));
    }

    public function test_callback_returns_error_for_invalid_platform(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/invalid/callback');

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('error', 'Invalid platform.');
    }

    public function test_redirect_with_empty_scopes_config(): void
    {
        // Set empty scopes config for a platform
        config(['services.linkedin.scopes' => '']);

        // Mock Socialite to avoid actual OAuth
        $mockDriver = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $mockDriver->shouldReceive('redirect')
            ->once()
            ->andReturn(new \Symfony\Component\HttpFoundation\RedirectResponse('https://linkedin.com/oauth'));

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('linkedin')
            ->once()
            ->andReturn($mockDriver);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/linkedin/redirect');

        $response->assertRedirect();
    }

    public function test_redirect_with_scopes_config(): void
    {
        // Set scopes config for a platform
        config(['services.linkedin.scopes' => 'openid,profile,w_member_social']);

        // Mock Socialite
        $mockDriver = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $mockDriver->shouldReceive('scopes')
            ->once()
            ->with(['openid', 'profile', 'w_member_social'])
            ->andReturnSelf();
        $mockDriver->shouldReceive('redirect')
            ->once()
            ->andReturn(new \Symfony\Component\HttpFoundation\RedirectResponse('https://linkedin.com/oauth'));

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('linkedin')
            ->once()
            ->andReturn($mockDriver);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/linkedin/redirect');

        $response->assertRedirect();
    }

    public function test_redirect_handles_socialite_exception(): void
    {
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->andThrow(new \Exception('OAuth configuration error'));

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/linkedin/redirect');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_callback_handles_exception_and_shows_error(): void
    {
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('linkedin')
            ->andThrow(new \Exception('OAuth callback failed'));

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/linkedin/callback');

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('error');
    }

    public function test_callback_success_stores_credentials_and_redirects_to_account_selection_for_facebook(): void
    {
        $mockSocialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mockSocialiteUser->token = 'test_access_token';
        $mockSocialiteUser->refreshToken = 'test_refresh_token';
        $mockSocialiteUser->expiresIn = 3600;
        $mockSocialiteUser->shouldReceive('getId')->andReturn('user_123');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn('TestUser');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Test User');

        $mockDriver = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $mockDriver->shouldReceive('user')->andReturn($mockSocialiteUser);

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('facebook')
            ->andReturn($mockDriver);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/facebook/callback');

        $response->assertRedirect(route('settings.social.select', ['platform' => 'facebook']));
        $response->assertSessionHas('info', 'Please select which account to publish to.');

        // Verify credentials were stored
        $tokenManager = app(TokenManager::class);
        $credentials = $tokenManager->getCredentials($this->brand->fresh(), 'facebook');
        $this->assertEquals('test_access_token', $credentials['access_token']);
        $this->assertEquals('user_123', $credentials['user_id']);
    }

    public function test_callback_success_stores_credentials_for_linkedin_without_account_selection(): void
    {
        $mockSocialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mockSocialiteUser->token = 'linkedin_access_token';
        $mockSocialiteUser->refreshToken = null;
        $mockSocialiteUser->expiresIn = 7200;
        $mockSocialiteUser->shouldReceive('getId')->andReturn('person_456');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn(null);
        $mockSocialiteUser->shouldReceive('getName')->andReturn('LinkedIn User');

        $mockDriver = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $mockDriver->shouldReceive('user')->andReturn($mockSocialiteUser);

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('linkedin')
            ->andReturn($mockDriver);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/linkedin/callback');

        $response->assertRedirect(route('settings.social'));
        $response->assertSessionHas('success', 'Linkedin connected successfully!');

        // Verify credentials were stored with person_id
        $tokenManager = app(TokenManager::class);
        $credentials = $tokenManager->getCredentials($this->brand->fresh(), 'linkedin');
        $this->assertEquals('linkedin_access_token', $credentials['access_token']);
        $this->assertEquals('person_456', $credentials['person_id']);
    }

    public function test_callback_success_for_pinterest_redirects_to_account_selection(): void
    {
        $mockSocialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mockSocialiteUser->token = 'pinterest_access_token';
        $mockSocialiteUser->refreshToken = 'pinterest_refresh_token';
        $mockSocialiteUser->expiresIn = 3600;
        $mockSocialiteUser->shouldReceive('getId')->andReturn('pinner_789');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn('PinterestUser');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Pinterest User');

        $mockDriver = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $mockDriver->shouldReceive('user')->andReturn($mockSocialiteUser);

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('pinterest')
            ->andReturn($mockDriver);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/pinterest/callback');

        $response->assertRedirect(route('settings.social.select', ['platform' => 'pinterest']));
        $response->assertSessionHas('info');

        // Verify Pinterest-specific fields
        $tokenManager = app(TokenManager::class);
        $credentials = $tokenManager->getCredentials($this->brand->fresh(), 'pinterest');
        $this->assertEquals('pinner_789', $credentials['user_id']);
    }

    public function test_instagram_redirect_uses_facebook_driver_with_instagram_redirect_url(): void
    {
        config(['services.instagram.redirect' => '/settings/social/instagram/callback']);
        config(['services.instagram.scopes' => 'instagram_basic,pages_show_list']);

        $mockDriver = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $mockDriver->shouldReceive('scopes')
            ->once()
            ->with(['instagram_basic', 'pages_show_list'])
            ->andReturnSelf();
        $mockDriver->shouldReceive('redirectUrl')
            ->once()
            ->andReturnSelf();
        $mockDriver->shouldReceive('redirect')
            ->once()
            ->andReturn(new \Symfony\Component\HttpFoundation\RedirectResponse('https://facebook.com/oauth'));

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('facebook')  // Instagram uses Facebook driver
            ->andReturn($mockDriver);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/instagram/redirect');

        $response->assertRedirect();
    }

    public function test_instagram_callback_uses_facebook_driver(): void
    {
        config(['services.instagram.redirect' => '/settings/social/instagram/callback']);

        $mockSocialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $mockSocialiteUser->token = 'instagram_access_token';
        $mockSocialiteUser->refreshToken = null;
        $mockSocialiteUser->expiresIn = 3600;
        $mockSocialiteUser->shouldReceive('getId')->andReturn('ig_user_123');
        $mockSocialiteUser->shouldReceive('getNickname')->andReturn('insta_user');
        $mockSocialiteUser->shouldReceive('getName')->andReturn('Instagram User');

        $mockDriver = Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $mockDriver->shouldReceive('redirectUrl')->andReturnSelf();
        $mockDriver->shouldReceive('user')->andReturn($mockSocialiteUser);

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('facebook')  // Instagram uses Facebook driver
            ->andReturn($mockDriver);

        $response = $this->actingAs($this->user)
            ->withSession(['current_account_id' => $this->account->id])
            ->get('/settings/social/instagram/callback');

        $response->assertRedirect(route('settings.social.select', ['platform' => 'instagram']));

        // Verify Instagram-specific fields
        $tokenManager = app(TokenManager::class);
        $credentials = $tokenManager->getCredentials($this->brand->fresh(), 'instagram');
        $this->assertEquals('ig_user_123', $credentials['instagram_account_id']);
    }
}
