<?php

namespace App\Services\SocialPlatforms;

class FacebookPlatform extends AbstractPlatform
{
    public function getIdentifier(): string
    {
        return 'facebook';
    }

    public function getDisplayName(): string
    {
        return 'Facebook';
    }

    public function getCharacterLimit(): int
    {
        return 63206;
    }

    public function getAiPromptInstructions(): string
    {
        return 'Create a conversational Facebook post that encourages engagement. Ask a question or invite comments. Keep it friendly and accessible.';
    }
}
