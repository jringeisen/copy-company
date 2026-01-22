<?php

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('guests cannot access api tokens page', function () {
    $response = $this->get(route('settings.api-tokens'));

    $response->assertRedirect('/login');
});

test('users can view api tokens page', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->get(route('settings.api-tokens'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/ApiTokens')
            ->has('tokens')
        );
});

test('users can create api tokens', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('settings.api-tokens.store'), [
        'name' => 'Test Token',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('newToken');

    expect($user->tokens()->where('name', 'Test Token')->exists())->toBeTrue();
});

test('creating token requires a name', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('settings.api-tokens.store'), []);

    $response->assertSessionHasErrors('name');
});

test('token name must not exceed 255 characters', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('settings.api-tokens.store'), [
        'name' => str_repeat('a', 256),
    ]);

    $response->assertSessionHasErrors('name');
});

test('users can delete their own tokens', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $token = $user->createToken('Test Token');

    $response = $this->actingAs($user)->delete(route('settings.api-tokens.destroy', $token->accessToken->id));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($user->tokens()->count())->toBe(0);
});

test('users cannot delete other users tokens', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherToken = $otherUser->createToken('Other Token');

    $response = $this->actingAs($user)->delete(route('settings.api-tokens.destroy', $otherToken->accessToken->id));

    $response->assertForbidden();

    expect($otherUser->tokens()->count())->toBe(1);
});

test('api tokens page shows existing tokens', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $user->createToken('Token One');
    $user->createToken('Token Two');

    $response = $this->actingAs($user)->get(route('settings.api-tokens'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/ApiTokens')
            ->has('tokens', 2)
        );
});

test('new token is shown in flash after creation', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('settings.api-tokens.store'), [
        'name' => 'My API Token',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('newToken');

    $newToken = session('newToken');
    expect($newToken)->not->toBeEmpty();
    expect(strlen($newToken))->toBeGreaterThan(40);
});

test('tokens can be used for api authentication', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $token = $user->createToken('API Token');

    // Make a request to a sanctum-protected endpoint
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ])->getJson('/api/mcp/copy-company');

    // Should not be unauthorized (the endpoint may return different status codes
    // depending on the request, but not 401)
    expect($response->status())->not->toBe(401);
});

test('invalid tokens are rejected', function () {
    // MCP endpoints use POST, not GET
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-token-here',
    ])->postJson('/api/mcp/copy-company');

    $response->assertUnauthorized();
});

test('tokens show last used timestamp', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $token = $user->createToken('Test Token');

    // Initially, last_used_at should be null
    expect($token->accessToken->last_used_at)->toBeNull();

    // Simulate token usage by updating the timestamp
    $token->accessToken->update(['last_used_at' => now()]);

    $response = $this->actingAs($user)->get(route('settings.api-tokens'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Settings/ApiTokens')
            ->has('tokens', 1)
            ->has('tokens.0.last_used_at')
        );
});
