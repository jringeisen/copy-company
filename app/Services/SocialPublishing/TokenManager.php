<?php

namespace App\Services\SocialPublishing;

use App\Models\Brand;
use App\Services\SocialPublishing\Contracts\TokenRefreshableInterface;

class TokenManager
{
    /**
     * Get credentials for a specific platform.
     *
     * @return array<string, mixed>|null
     */
    public function getCredentials(Brand $brand, string $platform): ?array
    {
        $connections = $brand->social_connections ?? [];

        return $connections[$platform] ?? null;
    }

    /**
     * Store credentials for a platform.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function storeCredentials(Brand $brand, string $platform, array $credentials): void
    {
        $connections = $brand->social_connections ?? [];
        $connections[$platform] = array_merge($credentials, [
            'connected_at' => now()->toDateTimeString(),
        ]);
        $brand->update(['social_connections' => $connections]);
    }

    /**
     * Remove credentials for a platform.
     */
    public function removeCredentials(Brand $brand, string $platform): void
    {
        $connections = $brand->social_connections ?? [];
        unset($connections[$platform]);
        $brand->update(['social_connections' => $connections]);
    }

    /**
     * Check if a platform is connected.
     */
    public function isConnected(Brand $brand, string $platform): bool
    {
        return ! empty($this->getCredentials($brand, $platform));
    }

    /**
     * Get all connected platforms for a brand.
     *
     * @return array<string>
     */
    public function getConnectedPlatforms(Brand $brand): array
    {
        $connections = $brand->social_connections ?? [];

        return array_keys($connections);
    }

    /**
     * Refresh token if needed.
     */
    public function refreshIfNeeded(Brand $brand, string $platform, TokenRefreshableInterface $publisher): bool
    {
        $credentials = $this->getCredentials($brand, $platform);

        if (! $credentials || ! $publisher->tokenNeedsRefresh($credentials)) {
            return false;
        }

        $newCredentials = $publisher->refreshToken($credentials);
        $this->storeCredentials($brand, $platform, array_merge($credentials, $newCredentials));

        return true;
    }

    /**
     * Get connection info for display (without sensitive data).
     *
     * @return array<string, mixed>|null
     */
    public function getConnectionInfo(Brand $brand, string $platform): ?array
    {
        $credentials = $this->getCredentials($brand, $platform);

        if (! $credentials) {
            return null;
        }

        return [
            'account_id' => $credentials['account_id'] ?? null,
            'account_name' => $credentials['account_name'] ?? null,
            'connected_at' => $credentials['connected_at'] ?? null,
            'expires_at' => $credentials['expires_at'] ?? null,
        ];
    }
}
