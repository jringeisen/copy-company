<?php

namespace Tests\Feature\Controllers;

use App\Models\Brand;
use App\Models\User;
use App\Services\SocialPublishing\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SocialSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->brand = Brand::factory()->create(['user_id' => $this->user->id]);
        $this->user->update(['current_brand_id' => $this->brand->id]);
    }

    public function test_guests_cannot_access_social_settings(): void
    {
        $response = $this->get('/settings/social');

        $response->assertRedirect('/login');
    }

    public function test_users_with_brand_can_view_social_settings(): void
    {
        $response = $this->actingAs($this->user)->get('/settings/social');

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/Social')
            ->has('platforms')
            ->has('brand')
        );
    }

    public function test_social_settings_shows_all_platforms(): void
    {
        $response = $this->actingAs($this->user)->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', 6)
            ->where('platforms.0.identifier', 'instagram')
            ->where('platforms.0.connected', false)
        );
    }

    public function test_social_settings_shows_connected_platforms(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
            'account_name' => '@testuser',
        ]);

        $response = $this->actingAs($this->user)->get('/settings/social');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('platforms', fn ($platforms) => $platforms
                ->where('5.identifier', 'twitter')
                ->where('5.connected', true)
                ->where('5.account_name', '@testuser')
                ->etc()
            )
        );
    }

    public function test_redirect_returns_error_for_invalid_platform(): void
    {
        $response = $this->actingAs($this->user)->get('/settings/social/invalid/redirect');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_disconnect_removes_platform_credentials(): void
    {
        $tokenManager = app(TokenManager::class);
        $tokenManager->storeCredentials($this->brand, 'twitter', [
            'access_token' => 'test_token',
        ]);

        $this->assertTrue($tokenManager->isConnected($this->brand, 'twitter'));

        $response = $this->actingAs($this->user)->delete('/settings/social/twitter');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->brand->refresh();
        $this->assertFalse($tokenManager->isConnected($this->brand, 'twitter'));
    }

    public function test_disconnect_returns_error_for_invalid_platform(): void
    {
        $response = $this->actingAs($this->user)->delete('/settings/social/invalid');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_users_without_brand_are_redirected_to_brand_create(): void
    {
        $userWithoutBrand = User::factory()->create();

        $response = $this->actingAs($userWithoutBrand)->get('/settings/social');

        $response->assertRedirect(route('brands.create'));
    }
}
