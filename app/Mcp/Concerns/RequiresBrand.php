<?php

namespace App\Mcp\Concerns;

use App\Models\Brand;
use App\Models\OAuthTokenContext;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Passport\Token;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\PermissionRegistrar;

trait RequiresBrand
{
    /**
     * Get the current brand for the authenticated user.
     */
    protected function getBrand(Request $request): Brand|Response
    {
        $user = $this->resolveAndAuthenticateUser($request);

        if (! $user) {
            return Response::error('Authentication required. Set MCP_API_TOKEN environment variable for local servers.');
        }

        // First, try to get brand from OAuth token context (for OAuth-authenticated requests)
        $brand = $this->getBrandFromOAuthToken($request);

        // Fall back to session-based brand selection (for local servers or Sanctum)
        if (! $brand) {
            $brand = $user->currentBrand();
        }

        if (! $brand) {
            return Response::error('No brand selected. Please set your current brand first.');
        }

        return $brand;
    }

    /**
     * Get the brand from OAuth token context if available.
     */
    protected function getBrandFromOAuthToken(Request $request): ?Brand
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        // Check if authenticated via Passport OAuth token
        $token = $user->token();

        if (! $token instanceof Token) {
            return null;
        }

        // Look up the brand context for this token
        $context = OAuthTokenContext::find($token->id);

        if (! $context) {
            return null;
        }

        // Verify the user still has access to this brand
        $account = $user->currentAccount();

        if (! $account) {
            return null;
        }

        $brand = $account->brands()->find($context->brand_id);

        if (! $brand) {
            return null;
        }

        return $brand;
    }

    /**
     * Resolve the user and set up authentication context for local servers.
     */
    protected function resolveAndAuthenticateUser(Request $request): ?User
    {
        // First try to get user from request (web server with auth middleware)
        if ($user = $request->user()) {
            return $user;
        }

        // For local servers, validate MCP_API_TOKEN (Sanctum token)
        if ($token = env('MCP_API_TOKEN')) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && $accessToken->tokenable instanceof User) {
                $user = $accessToken->tokenable;

                // Update last used timestamp
                $accessToken->forceFill(['last_used_at' => now()])->save();

                // Set the user as authenticated for Gate checks
                Auth::login($user);

                // Set Spatie permissions team context
                $account = $user->currentAccount();
                if ($account) {
                    app(PermissionRegistrar::class)->setPermissionsTeamId($account->id);
                }

                return $user;
            }
        }

        return null;
    }
}
