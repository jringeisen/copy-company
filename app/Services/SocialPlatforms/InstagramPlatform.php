<?php

namespace App\Services\SocialPlatforms;

class InstagramPlatform extends AbstractPlatform
{
    public function getIdentifier(): string
    {
        return 'instagram';
    }

    public function getDisplayName(): string
    {
        return 'Instagram';
    }

    public function getCharacterLimit(): int
    {
        return 2200;
    }

    public function getAiPromptInstructions(): string
    {
        return 'Create an engaging Instagram caption. Start with a hook, include relevant emojis sparingly, and end with a call-to-action. Include 5-10 relevant hashtags at the end.';
    }
}
