<?php

namespace App\Services\SocialPlatforms;

use App\Enums\SocialPlatform;
use InvalidArgumentException;

class PlatformFactory
{
    /**
     * @var array<string, class-string<PlatformInterface>>
     */
    protected static array $platforms = [
        'instagram' => InstagramPlatform::class,
        'facebook' => FacebookPlatform::class,
        'linkedin' => LinkedInPlatform::class,
        'pinterest' => PinterestPlatform::class,
        'tiktok' => TikTokPlatform::class,
    ];

    /**
     * Create a platform instance from an enum.
     */
    public static function fromEnum(SocialPlatform $platform): PlatformInterface
    {
        return self::make($platform->value);
    }

    /**
     * Create a platform instance from a string identifier.
     */
    public static function make(string $identifier): PlatformInterface
    {
        $platformClass = self::$platforms[$identifier] ?? null;

        if (! $platformClass) {
            throw new InvalidArgumentException("Unknown platform: {$identifier}");
        }

        return new $platformClass;
    }

    /**
     * Check if a platform identifier is supported.
     */
    public static function isSupported(string $identifier): bool
    {
        return isset(self::$platforms[$identifier]);
    }

    /**
     * Get all supported platform instances.
     *
     * @return array<string, PlatformInterface>
     */
    public static function all(): array
    {
        $instances = [];

        foreach (self::$platforms as $identifier => $class) {
            $instances[$identifier] = new $class;
        }

        return $instances;
    }

    /**
     * Get all supported platform identifiers.
     *
     * @return array<string>
     */
    public static function supportedIdentifiers(): array
    {
        return array_keys(self::$platforms);
    }
}
