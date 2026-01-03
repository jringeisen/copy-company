<?php

namespace App\Services\SocialPlatforms;

class TikTokPlatform extends AbstractPlatform
{
    public function getIdentifier(): string
    {
        return 'tiktok';
    }

    public function getDisplayName(): string
    {
        return 'TikTok';
    }

    public function getCharacterLimit(): int
    {
        return 2200;
    }

    public function getAiPromptInstructions(): string
    {
        return 'Create a TikTok caption. Keep it short, fun, and trendy. Use relevant hashtags including trending ones if applicable. Make it feel authentic and casual.';
    }
}
