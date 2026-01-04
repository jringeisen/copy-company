<?php

namespace App\Services\SocialPublishing\Contracts;

interface TokenRefreshableInterface
{
    /**
     * Check if token needs refresh.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function tokenNeedsRefresh(array $credentials): bool;

    /**
     * Refresh the access token.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string}
     */
    public function refreshToken(array $credentials): array;
}
