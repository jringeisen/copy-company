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
}
