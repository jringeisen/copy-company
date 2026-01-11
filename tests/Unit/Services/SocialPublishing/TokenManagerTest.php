<?php

namespace Tests\Unit\Services\SocialPublishing;

use App\Models\Account;
use App\Models\Brand;
use App\Models\User;
use App\Services\SocialPublishing\Contracts\TokenRefreshableInterface;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TokenManagerTest extends TestCase
{
    use RefreshDatabase;

    protected TokenManager $tokenManager;

    protected Brand $brand;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenManager = new TokenManager;
        $user = User::factory()->create();
        $this->account = Account::factory()->create();
        $this->account->users()->attach($user->id, ['role' => 'admin']);
        $this->brand = Brand::factory()->forAccount($this->account)->create();
    }

    public function test_get_credentials_returns_null_when_not_connected(): void
    {
        $credentials = $this->tokenManager->getCredentials($this->brand, 'facebook');

        $this->assertNull($credentials);
    }

    public function test_store_credentials_saves_to_brand(): void
    {
        $credentials = [
            'access_token' => 'test_token',
            'refresh_token' => 'test_refresh',
            'account_id' => '12345',
            'account_name' => '@testuser',
        ];

        $this->tokenManager->storeCredentials($this->brand, 'facebook', $credentials);

        $this->brand->refresh();
        $storedCredentials = $this->tokenManager->getCredentials($this->brand, 'facebook');

        $this->assertEquals('test_token', $storedCredentials['access_token']);
        $this->assertEquals('@testuser', $storedCredentials['account_name']);
        $this->assertArrayHasKey('connected_at', $storedCredentials);
    }

    public function test_remove_credentials_removes_platform(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
        ]);

        $this->assertTrue($this->tokenManager->isConnected($this->brand, 'facebook'));

        $this->tokenManager->removeCredentials($this->brand, 'facebook');

        $this->assertFalse($this->tokenManager->isConnected($this->brand, 'facebook'));
    }

    public function test_is_connected_returns_true_when_credentials_exist(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'test_token',
        ]);

        $this->assertTrue($this->tokenManager->isConnected($this->brand, 'facebook'));
        $this->assertFalse($this->tokenManager->isConnected($this->brand, 'facebook'));
    }

    public function test_get_connected_platforms_returns_list_of_platforms(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'facebook', ['access_token' => 'token1']);
        $this->tokenManager->storeCredentials($this->brand, 'facebook', ['access_token' => 'token2']);

        $platforms = $this->tokenManager->getConnectedPlatforms($this->brand);

        $this->assertContains('facebook', $platforms);
        $this->assertContains('facebook', $platforms);
        $this->assertCount(2, $platforms);
    }

    public function test_get_connection_info_returns_safe_data(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'secret_token',
            'refresh_token' => 'secret_refresh',
            'account_id' => '12345',
            'account_name' => '@testuser',
            'expires_at' => '2026-02-01 12:00:00',
        ]);

        $info = $this->tokenManager->getConnectionInfo($this->brand, 'facebook');

        $this->assertEquals('12345', $info['account_id']);
        $this->assertEquals('@testuser', $info['account_name']);
        $this->assertArrayNotHasKey('access_token', $info);
        $this->assertArrayNotHasKey('refresh_token', $info);
    }

    public function test_get_connection_info_returns_null_when_not_connected(): void
    {
        $info = $this->tokenManager->getConnectionInfo($this->brand, 'facebook');

        $this->assertNull($info);
    }

    public function test_refresh_if_needed_calls_refresh_when_token_needs_refresh(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'old_token',
            'refresh_token' => 'refresh_token',
            'expires_at' => now()->subHour()->toDateTimeString(),
        ]);

        $publisher = Mockery::mock(TokenRefreshableInterface::class);
        $publisher->shouldReceive('tokenNeedsRefresh')->andReturn(true);
        $publisher->shouldReceive('refreshToken')->andReturn([
            'access_token' => 'new_token',
            'refresh_token' => 'new_refresh',
            'expires_at' => now()->addDay()->toDateTimeString(),
        ]);

        $result = $this->tokenManager->refreshIfNeeded($this->brand, 'facebook', $publisher);

        $this->assertTrue($result);

        $credentials = $this->tokenManager->getCredentials($this->brand, 'facebook');
        $this->assertEquals('new_token', $credentials['access_token']);
    }

    public function test_refresh_if_needed_returns_false_when_no_refresh_needed(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'current_token',
            'expires_at' => now()->addWeek()->toDateTimeString(),
        ]);

        $publisher = Mockery::mock(TokenRefreshableInterface::class);
        $publisher->shouldReceive('tokenNeedsRefresh')->andReturn(false);

        $result = $this->tokenManager->refreshIfNeeded($this->brand, 'facebook', $publisher);

        $this->assertFalse($result);
    }

    public function test_multiple_platforms_stored_independently(): void
    {
        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'twitter_token',
            'account_name' => '@twitter_user',
        ]);

        $this->tokenManager->storeCredentials($this->brand, 'facebook', [
            'access_token' => 'facebook_token',
            'account_name' => 'Facebook User',
        ]);

        $twitterCreds = $this->tokenManager->getCredentials($this->brand, 'facebook');
        $facebookCreds = $this->tokenManager->getCredentials($this->brand, 'facebook');

        $this->assertEquals('twitter_token', $twitterCreds['access_token']);
        $this->assertEquals('facebook_token', $facebookCreds['access_token']);
    }
}
