<?php

namespace App\Services\SocialPublishing\Contracts;

use App\Models\SocialPost;

interface PublisherInterface
{
    /**
     * Get the platform identifier (matches SocialPlatform enum value).
     */
    public function getPlatform(): string;

    /**
     * Publish a social post to the platform.
     *
     * @return array{success: bool, external_id: ?string, error: ?string}
     */
    public function publish(SocialPost $socialPost, array $credentials): array;

    /**
     * Get required OAuth scopes for publishing.
     *
     * @return array<string>
     */
    public function getRequiredScopes(): array;

    /**
     * Validate that credentials are sufficient for publishing.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(array $credentials): bool;
}
