<?php

namespace App\Services\SocialPlatforms;

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;

class MediaLimits
{
    /**
     * Maximum number of images allowed per platform and format.
     * A value of 0 means no images allowed (video only).
     *
     * @var array<string, array<string, int>>
     */
    public const LIMITS = [
        'instagram' => [
            'feed' => 10,
            'story' => 1,
            'reel' => 0, // Video only
            'carousel' => 10,
        ],
        'facebook' => [
            'feed' => 10,
            'story' => 1,
            'reel' => 0, // Video only
        ],
        'twitter' => [
            'feed' => 4,
            'thread' => 4,
        ],
        'linkedin' => [
            'feed' => 9,
        ],
        'pinterest' => [
            'pin' => 5,
        ],
        'tiktok' => [
            'feed' => 0, // Video only
            'story' => 0, // Video only
        ],
    ];

    /**
     * Get the maximum number of images allowed for a platform and format.
     */
    public static function getLimit(SocialPlatform|string $platform, SocialFormat|string $format): int
    {
        $platformValue = $platform instanceof SocialPlatform ? $platform->value : $platform;
        $formatValue = $format instanceof SocialFormat ? $format->value : $format;

        return self::LIMITS[$platformValue][$formatValue] ?? 0;
    }

    /**
     * Check if a platform/format combination allows images.
     */
    public static function allowsImages(SocialPlatform|string $platform, SocialFormat|string $format): bool
    {
        return self::getLimit($platform, $format) > 0;
    }

    /**
     * Validate the number of images for a platform/format.
     *
     * @param  array<int>  $mediaIds
     */
    public static function validateMediaCount(
        SocialPlatform|string $platform,
        SocialFormat|string $format,
        array $mediaIds
    ): bool {
        $limit = self::getLimit($platform, $format);

        return count($mediaIds) <= $limit;
    }

    /**
     * Get all limits for a specific platform.
     *
     * @return array<string, int>
     */
    public static function getLimitsForPlatform(SocialPlatform|string $platform): array
    {
        $platformValue = $platform instanceof SocialPlatform ? $platform->value : $platform;

        return self::LIMITS[$platformValue] ?? [];
    }

    /**
     * Get available formats for a platform that support images.
     *
     * @return array<string>
     */
    public static function getImageFormatsForPlatform(SocialPlatform|string $platform): array
    {
        $limits = self::getLimitsForPlatform($platform);

        return array_keys(array_filter($limits, fn (int $limit): bool => $limit > 0));
    }

    /**
     * Get a human-readable message about the limit.
     */
    public static function getLimitMessage(SocialPlatform|string $platform, SocialFormat|string $format): string
    {
        $limit = self::getLimit($platform, $format);

        if ($limit === 0) {
            return 'This format does not support images (video only).';
        }

        return $limit === 1
            ? 'You can attach 1 image.'
            : "You can attach up to {$limit} images.";
    }
}
