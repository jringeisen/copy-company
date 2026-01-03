<?php

namespace App\Services\SocialPlatforms;

class LinkedInPlatform extends AbstractPlatform
{
    public function getIdentifier(): string
    {
        return 'linkedin';
    }

    public function getDisplayName(): string
    {
        return 'LinkedIn';
    }

    public function getCharacterLimit(): int
    {
        return 3000;
    }

    public function getAiPromptInstructions(): string
    {
        return 'Create a professional LinkedIn post. Start with a strong opening line. Use line breaks for readability. Include insights or takeaways. End with a thought-provoking question.';
    }
}
