<?php

namespace App\Mcp\Concerns;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
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

        $brand = $user->currentBrand();

        if (! $brand) {
            return Response::error('No brand selected. Please set your current brand first.');
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
