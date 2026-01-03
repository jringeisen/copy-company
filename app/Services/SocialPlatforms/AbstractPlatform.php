<?php

namespace App\Services\SocialPlatforms;

abstract class AbstractPlatform implements PlatformInterface
{
    /**
     * Check if the given content exceeds the platform's character limit.
     */
    public function exceedsCharacterLimit(string $content): bool
    {
        return strlen($content) > $this->getCharacterLimit();
    }
}
