<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case Pinterest = 'pinterest';
    case LinkedIn = 'linkedin';
    case TikTok = 'tiktok';

    public function displayName(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook',
            self::Pinterest => 'Pinterest',
            self::LinkedIn => 'LinkedIn',
            self::TikTok => 'TikTok',
        };
    }

    /**
     * Get all platforms as dropdown options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function toDropdownOptions(bool $includeAll = false): array
    {
        $options = [];

        if ($includeAll) {
            $options[] = ['value' => 'all', 'label' => 'All Platforms'];
        }

        foreach (self::cases() as $platform) {
            $options[] = [
                'value' => $platform->value,
                'label' => $platform->displayName(),
            ];
        }

        return $options;
    }

    /**
     * Check if this platform requires media (image or video).
     */
    public function requiresMedia(): bool
    {
        return match ($this) {
            self::Instagram => true,
            self::Pinterest => true,
            self::TikTok => true,
            self::Facebook => false,
            self::LinkedIn => false,
        };
    }

    /**
     * Check if this platform requires video specifically.
     */
    public function requiresVideo(): bool
    {
        return match ($this) {
            self::TikTok => true,
            default => false,
        };
    }

    /**
     * Get the maximum character limit for this platform.
     */
    public function maxCharacters(): int
    {
        return match ($this) {
            self::Instagram => 2200,
            self::Facebook => 63206,
            self::Pinterest => 500,
            self::LinkedIn => 3000,
            self::TikTok => 2200,
        };
    }

    /**
     * Get a human-readable description of requirements.
     */
    public function requirementsDescription(): string
    {
        return match ($this) {
            self::Instagram => 'Requires an image or video',
            self::Pinterest => 'Requires an image',
            self::TikTok => 'Requires a video',
            self::Facebook => 'Text, images, or video',
            self::LinkedIn => 'Text, images, or video',
        };
    }
}
