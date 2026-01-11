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
}
