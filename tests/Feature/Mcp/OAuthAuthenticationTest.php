<?php

use App\Listeners\StoreOAuthTokenContext;
use App\Mcp\Servers\CopyCompanyServer;
use App\Mcp\Tools\Posts\ListPostsTool;
use App\Models\Brand;
use App\Models\OAuthTokenContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('posts.create', 'web');
    Permission::findOrCreate('posts.update', 'web');
    Permission::findOrCreate('posts.delete', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'posts.create',
        'posts.update',
        'posts.delete',
    ]);

    config([
        'services.stripe.prices.starter_monthly' => 'price_starter_monthly',
        'services.stripe.prices.starter_annual' => 'price_starter_annual',
        'services.stripe.prices.creator_monthly' => 'price_creator_monthly',
        'services.stripe.prices.creator_annual' => 'price_creator_annual',
        'services.stripe.prices.pro_monthly' => 'price_pro_monthly',
        'services.stripe.prices.pro_annual' => 'price_pro_annual',
    ]);
});

test('OAuth metadata endpoint returns proper discovery document', function () {
    $response = $this->get('/.well-known/oauth-authorization-server');

    $response->assertOk();
    $response->assertJsonStructure([
        'issuer',
        'authorization_endpoint',
        'token_endpoint',
        'registration_endpoint',
    ]);
});

test('MCP web endpoint requires authentication', function () {
    // MCP endpoints use SSE/POST - unauthenticated requests redirect to login
    $response = $this->post('/mcp/copy-company');

    // For HTML clients, Passport redirects to login (302) instead of 401
    // Both are acceptable authentication enforcement behaviors
    expect($response->status())->toBeIn([302, 401]);
});

test('MCP tools work with Passport OAuth authentication', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $account = $user->accounts()->first();
    setPermissionsTeamId($account->id);
    $user->assignRole('admin');

    // Create OAuth token context for the user
    $tokenId = 'test-oauth-token-id';
    OAuthTokenContext::create([
        'access_token_id' => $tokenId,
        'brand_id' => $brand->id,
    ]);

    // Use Passport's actingAs for OAuth authentication
    Passport::actingAs($user, ['mcp:use']);

    $response = CopyCompanyServer::actingAs($user)->tool(ListPostsTool::class, []);

    $response->assertOk();
});

test('StoreOAuthTokenContext listener stores brand context', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    // Simulate the request having a brand_id parameter
    request()->merge(['brand_id' => $brand->id]);

    $event = new AccessTokenCreated(
        tokenId: 'test-token-123',
        userId: $user->id,
        clientId: 'test-client-id'
    );

    $listener = new StoreOAuthTokenContext;
    $listener->handle($event);

    $context = OAuthTokenContext::find('test-token-123');

    expect($context)->not->toBeNull();
    expect($context->brand_id)->toBe($brand->id);
});

test('StoreOAuthTokenContext listener validates brand ownership', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    // Create another user with their own brand
    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();

    // Try to use the other user's brand
    request()->merge(['brand_id' => $otherBrand->id]);

    $event = new AccessTokenCreated(
        tokenId: 'test-token-456',
        userId: $user->id,
        clientId: 'test-client-id'
    );

    $listener = new StoreOAuthTokenContext;

    expect(fn () => $listener->handle($event))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);

    // Token context should not be created
    expect(OAuthTokenContext::find('test-token-456'))->toBeNull();
});

test('StoreOAuthTokenContext listener skips when no brand_id provided', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    // Don't set brand_id in request
    request()->merge([]);

    $event = new AccessTokenCreated(
        tokenId: 'test-token-789',
        userId: $user->id,
        clientId: 'test-client-id'
    );

    $listener = new StoreOAuthTokenContext;
    $listener->handle($event);

    // No context should be created
    expect(OAuthTokenContext::find('test-token-789'))->toBeNull();
});

test('OAuthTokenContext model belongs to brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $context = OAuthTokenContext::create([
        'access_token_id' => 'test-token-abc',
        'brand_id' => $brand->id,
    ]);

    expect($context->brand->id)->toBe($brand->id);
    expect($context->brand->name)->toBe($brand->name);
});

test('OAuthTokenContext is deleted when brand is deleted', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    OAuthTokenContext::create([
        'access_token_id' => 'test-token-cascade',
        'brand_id' => $brand->id,
    ]);

    expect(OAuthTokenContext::find('test-token-cascade'))->not->toBeNull();

    $brand->delete();

    expect(OAuthTokenContext::find('test-token-cascade'))->toBeNull();
});

test('authorization view is accessible for authenticated users', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $client = Client::factory()->create([
        'name' => 'Test MCP Client',
        'redirect_uris' => ['http://localhost/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost/callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
    ]));

    // Should render the authorization view (not redirect)
    $response->assertOk();
    $response->assertSee('Authorize');
});

test('authorization view includes brand selection', function () {
    $user = User::factory()->create();
    $brand1 = Brand::factory()->forUser($user)->create(['name' => 'Brand One']);
    $brand2 = Brand::factory()->forUser($user)->create(['name' => 'Brand Two']);

    $client = Client::factory()->create([
        'name' => 'Test MCP Client',
        'redirect_uris' => ['http://localhost/callback'],
        'grant_types' => ['authorization_code'],
        'revoked' => false,
    ]);

    $response = $this->actingAs($user)->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost/callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
    ]));

    $response->assertOk();
    $response->assertSee('Brand One');
    $response->assertSee('Brand Two');
    $response->assertSee('Select Brand');
});
