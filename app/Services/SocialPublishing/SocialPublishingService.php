<?php

namespace App\Services\SocialPublishing;

use App\Enums\SocialPostStatus;
use App\Models\SocialPost;
use App\Services\SocialPublishing\Contracts\TokenRefreshableInterface;
use Illuminate\Support\Facades\Log;

class SocialPublishingService
{
    public function __construct(
        protected TokenManager $tokenManager
    ) {}

    /**
     * Publish a social post to its platform.
     *
     * @return array{success: bool, external_id: ?string, error: ?string}
     */
    public function publish(SocialPost $socialPost): array
    {
        $brand = $socialPost->brand;
        $platform = $socialPost->platform->value;

        // Check if platform is connected
        if (! $this->tokenManager->isConnected($brand, $platform)) {
            return [
                'success' => false,
                'external_id' => null,
                'error' => "Platform '{$platform}' is not connected for this brand.",
            ];
        }

        // Get publisher
        if (! PublisherFactory::isSupported($platform)) {
            return [
                'success' => false,
                'external_id' => null,
                'error' => "Publishing to '{$platform}' is not yet supported.",
            ];
        }

        $publisher = PublisherFactory::make($platform);
        $credentials = $this->tokenManager->getCredentials($brand, $platform);

        // Refresh token if needed
        if ($publisher instanceof TokenRefreshableInterface) {
            try {
                $this->tokenManager->refreshIfNeeded($brand, $platform, $publisher);
                // Re-fetch credentials after potential refresh
                $credentials = $this->tokenManager->getCredentials($brand, $platform);
            } catch (\Exception $e) {
                Log::error("Token refresh failed for {$platform}", [
                    'brand_id' => $brand->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'external_id' => null,
                    'error' => 'Token refresh failed. Please reconnect your account.',
                ];
            }
        }

        // Validate credentials
        if (! $publisher->validateCredentials($credentials)) {
            return [
                'success' => false,
                'external_id' => null,
                'error' => 'Invalid or expired credentials. Please reconnect your account.',
            ];
        }

        // Publish
        return $publisher->publish($socialPost, $credentials);
    }

    /**
     * Publish and update the social post status.
     */
    public function publishAndUpdateStatus(SocialPost $socialPost): bool
    {
        $result = $this->publish($socialPost);

        if ($result['success']) {
            $socialPost->update([
                'status' => SocialPostStatus::Published,
                'published_at' => now(),
                'external_id' => $result['external_id'],
                'failure_reason' => null,
            ]);

            return true;
        }

        $socialPost->update([
            'status' => SocialPostStatus::Failed,
            'failure_reason' => $result['error'],
        ]);

        return false;
    }

    /**
     * Check if a platform can publish (has valid connection).
     */
    public function canPublish(SocialPost $socialPost): bool
    {
        $platform = $socialPost->platform->value;

        if (! PublisherFactory::isSupported($platform)) {
            return false;
        }

        return $this->tokenManager->isConnected($socialPost->brand, $platform);
    }
}
