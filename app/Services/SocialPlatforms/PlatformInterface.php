<?php

namespace App\Services\SocialPlatforms;

interface PlatformInterface
{
    /**
     * Get the platform's identifier (matches SocialPlatform enum value).
     */
    public function getIdentifier(): string;

    /**
     * Get the platform's display name.
     */
    public function getDisplayName(): string;

    /**
     * Get the character limit for posts on this platform.
     */
    public function getCharacterLimit(): int;

    /**
     * Get the AI prompt instructions for generating content for this platform.
     */
    public function getAiPromptInstructions(): string;

    /**
     * Check if the given content exceeds the platform's character limit.
     */
    public function exceedsCharacterLimit(string $content): bool;
}
