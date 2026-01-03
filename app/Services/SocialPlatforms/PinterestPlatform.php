<?php

namespace App\Services\SocialPlatforms;

class PinterestPlatform extends AbstractPlatform
{
    public function getIdentifier(): string
    {
        return 'pinterest';
    }

    public function getDisplayName(): string
    {
        return 'Pinterest';
    }

    public function getCharacterLimit(): int
    {
        return 500;
    }

    public function getAiPromptInstructions(): string
    {
        return 'Create a Pinterest pin description. Focus on keywords and searchability. Be descriptive about what value the content provides. Include relevant keywords naturally.';
    }
}
