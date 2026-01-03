<?php

namespace App\Services\SocialPlatforms;

class TwitterPlatform extends AbstractPlatform
{
    public function getIdentifier(): string
    {
        return 'twitter';
    }

    public function getDisplayName(): string
    {
        return 'X (Twitter)';
    }

    public function getCharacterLimit(): int
    {
        return 280;
    }

    public function getAiPromptInstructions(): string
    {
        return 'Create a concise, punchy tweet. Make every word count. If the content is too long, create a thread with 2-3 tweets. Use 1-2 relevant hashtags maximum.';
    }
}
