<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case Pinterest = 'pinterest';
    case LinkedIn = 'linkedin';
    case TikTok = 'tiktok';
    case Twitter = 'twitter';

    public function displayName(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook',
            self::Pinterest => 'Pinterest',
            self::LinkedIn => 'LinkedIn',
            self::TikTok => 'TikTok',
            self::Twitter => 'X (Twitter)',
        };
    }
}
