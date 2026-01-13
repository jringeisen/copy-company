<?php

use App\Models\Brand;
use App\Models\User;
use App\Services\SocialPublishing\FacebookPagesService;
use App\Services\SocialPublishing\InstagramAccountsService;
use App\Services\SocialPublishing\PinterestBoardsService;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('settings.social', 'web');
    Permission::findOrCreate('brands.update', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo(['settings.social', 'brands.update']);
});

function setupSocialUser(): array
{
    $user = User::factory()->create();
    $account = \App\Models\Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    return [$user, $brand, $account];
}

test('guests cannot access social settings', function () {
    $response = $this->get(route('settings.social'));

    $response->assertRedirect('/login');
});

test('users can view social settings page', function () {
    [$user, $brand] = setupSocialUser();

    $response = $this->actingAs($user)->get(route('settings.social'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/Social')
        ->has('platforms')
        ->has('brand')
    );
});

test('platforms show connected status correctly', function () {
    [$user, $brand] = setupSocialUser();

    // Mock token manager to return connected status
    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getConnectionInfo')
        ->andReturn(['account_name' => 'Test Account', 'connected_at' => now()->toIso8601String()]);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token', 'page_id' => '123', 'page_name' => 'Test Page']);

    $this->app->instance(TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->get(route('settings.social'));

    $response->assertOk();
});

test('redirect to invalid platform returns error', function () {
    [$user, $brand] = setupSocialUser();

    $response = $this->actingAs($user)->get(route('settings.social.redirect', 'invalid'));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Invalid platform.');
});

test('disconnect invalid platform returns error', function () {
    [$user, $brand] = setupSocialUser();

    $response = $this->actingAs($user)->delete(route('settings.social.disconnect', 'invalid'));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Invalid platform.');
});

test('disconnect valid platform removes credentials', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('removeCredentials')
        ->once()
        ->with(Mockery::type(Brand::class), 'facebook');

    $this->app->instance(TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->delete(route('settings.social.disconnect', 'facebook'));

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('callback with invalid platform returns error', function () {
    [$user, $brand] = setupSocialUser();

    $response = $this->actingAs($user)->get(route('settings.social.callback', 'invalid'));

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('error', 'Invalid platform.');
});

test('callback without brand redirects to brand creation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.social.callback', 'facebook'));

    $response->assertRedirect(route('brands.create'));
});

test('account selection for non-supported platform redirects with error', function () {
    [$user, $brand] = setupSocialUser();

    $response = $this->actingAs($user)->get(route('settings.social.select', 'linkedin'));

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('error', 'Account selection is not required for this platform.');
});

test('account selection without brand redirects to brand creation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.social.select', 'facebook'));

    $response->assertRedirect(route('brands.create'));
});

test('account selection without credentials redirects with error', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(null);

    $this->app->instance(TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->get(route('settings.social.select', 'facebook'));

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('error');
});

test('facebook account selection shows pages', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token']);

    $facebookPagesService = Mockery::mock(FacebookPagesService::class);
    $facebookPagesService->shouldReceive('fetchUserPages')
        ->with('test-token')
        ->andReturn([
            ['id' => '123', 'name' => 'Test Page', 'access_token' => 'page-token'],
        ]);

    $this->app->instance(TokenManager::class, $tokenManager);
    $this->app->instance(FacebookPagesService::class, $facebookPagesService);

    $response = $this->actingAs($user)->get(route('settings.social.select', 'facebook'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/SocialAccountSelect')
        ->where('platform', 'facebook')
        ->where('accountType', 'page')
        ->has('accounts', 1)
    );
});

test('instagram account selection shows accounts', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token']);

    $instagramAccountsService = Mockery::mock(InstagramAccountsService::class);
    $instagramAccountsService->shouldReceive('fetchInstagramAccounts')
        ->with('test-token')
        ->andReturn([
            ['id' => '456', 'username' => 'testaccount'],
        ]);

    $this->app->instance(TokenManager::class, $tokenManager);
    $this->app->instance(InstagramAccountsService::class, $instagramAccountsService);

    $response = $this->actingAs($user)->get(route('settings.social.select', 'instagram'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/SocialAccountSelect')
        ->where('platform', 'instagram')
        ->where('accountType', 'account')
    );
});

test('pinterest account selection shows boards', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token']);

    $pinterestBoardsService = Mockery::mock(PinterestBoardsService::class);
    $pinterestBoardsService->shouldReceive('fetchUserBoards')
        ->with('test-token')
        ->andReturn([
            ['id' => '789', 'name' => 'Test Board'],
        ]);

    $this->app->instance(TokenManager::class, $tokenManager);
    $this->app->instance(PinterestBoardsService::class, $pinterestBoardsService);

    $response = $this->actingAs($user)->get(route('settings.social.select', 'pinterest'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/SocialAccountSelect')
        ->where('platform', 'pinterest')
        ->where('accountType', 'board')
    );
});

test('account selection with no accounts redirects with error', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token']);

    $facebookPagesService = Mockery::mock(FacebookPagesService::class);
    $facebookPagesService->shouldReceive('fetchUserPages')
        ->andReturn([]);

    $this->app->instance(TokenManager::class, $tokenManager);
    $this->app->instance(FacebookPagesService::class, $facebookPagesService);

    $response = $this->actingAs($user)->get(route('settings.social.select', 'facebook'));

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('error');
});

test('instagram with no accounts shows specific error message', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token']);

    $instagramAccountsService = Mockery::mock(InstagramAccountsService::class);
    $instagramAccountsService->shouldReceive('fetchInstagramAccounts')
        ->andReturn([]);

    $this->app->instance(TokenManager::class, $tokenManager);
    $this->app->instance(InstagramAccountsService::class, $instagramAccountsService);

    $response = $this->actingAs($user)->get(route('settings.social.select', 'instagram'));

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('error', 'No Instagram Business accounts found. Please make sure you have an Instagram Business or Creator account connected to a Facebook Page.');
});

test('store account selection for invalid platform returns error', function () {
    [$user, $brand] = setupSocialUser();

    $response = $this->actingAs($user)->post(route('settings.social.select.store', 'linkedin'), [
        'account_id' => '123',
        'account_name' => 'Test',
    ]);

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('error', 'Invalid platform.');
});

test('store account selection without brand redirects to brand creation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('settings.social.select.store', 'facebook'), [
        'account_id' => '123',
        'account_name' => 'Test',
    ]);

    $response->assertRedirect(route('brands.create'));
});

test('store account selection without credentials redirects with error', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(null);

    $this->app->instance(TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('settings.social.select.store', 'facebook'), [
        'account_id' => '123',
        'account_name' => 'Test',
    ]);

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('error', 'Please connect your account first.');
});

test('store facebook account selection updates credentials', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token']);
    $tokenManager->shouldReceive('storeCredentials')
        ->once()
        ->with(
            Mockery::type(Brand::class),
            'facebook',
            Mockery::on(fn (array $creds) => $creds['page_id'] === '123' && $creds['page_name'] === 'Test Page' && $creds['page_access_token'] === 'page-token'
            )
        );

    $this->app->instance(TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('settings.social.select.store', 'facebook'), [
        'account_id' => '123',
        'account_name' => 'Test Page',
        'access_token' => 'page-token',
    ]);

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('success', 'Facebook configured successfully!');
});

test('store instagram account selection updates credentials', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token']);
    $tokenManager->shouldReceive('storeCredentials')
        ->once()
        ->with(
            Mockery::type(Brand::class),
            'instagram',
            Mockery::on(fn (array $creds) => $creds['instagram_account_id'] === '456' && $creds['instagram_username'] === 'testuser'
            )
        );

    $this->app->instance(TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('settings.social.select.store', 'instagram'), [
        'account_id' => '456',
        'account_name' => 'testuser',
        'access_token' => 'page-token',
    ]);

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('success', 'Instagram configured successfully!');
});

test('store pinterest account selection updates credentials', function () {
    [$user, $brand] = setupSocialUser();

    $tokenManager = Mockery::mock(TokenManager::class);
    $tokenManager->shouldReceive('getCredentials')
        ->andReturn(['access_token' => 'test-token']);
    $tokenManager->shouldReceive('storeCredentials')
        ->once()
        ->with(
            Mockery::type(Brand::class),
            'pinterest',
            Mockery::on(fn (array $creds) => $creds['board_id'] === '789' && $creds['board_name'] === 'My Board'
            )
        );

    $this->app->instance(TokenManager::class, $tokenManager);

    $response = $this->actingAs($user)->post(route('settings.social.select.store', 'pinterest'), [
        'account_id' => '789',
        'account_name' => 'My Board',
    ]);

    $response->assertRedirect(route('settings.social'));
    $response->assertSessionHas('success', 'Pinterest configured successfully!');
});

test('disconnect without brand redirects to brand creation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('settings.social.disconnect', 'facebook'));

    $response->assertRedirect(route('brands.create'));
});
