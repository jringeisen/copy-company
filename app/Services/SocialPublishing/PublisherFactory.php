<?php

namespace App\Services\SocialPublishing;

use App\Enums\SocialPlatform;
use App\Services\SocialPublishing\Contracts\PublisherInterface;
use App\Services\SocialPublishing\Publishers\FacebookPublisher;
use App\Services\SocialPublishing\Publishers\InstagramPublisher;
use App\Services\SocialPublishing\Publishers\LinkedInPublisher;
use App\Services\SocialPublishing\Publishers\PinterestPublisher;
use App\Services\SocialPublishing\Publishers\TikTokPublisher;
use App\Services\SocialPublishing\Publishers\TwitterPublisher;
use InvalidArgumentException;

class PublisherFactory
{
    /**
     * @var array<string, class-string<PublisherInterface>>
     */
    protected static array $publishers = [
        'twitter' => TwitterPublisher::class,
        'facebook' => FacebookPublisher::class,
        'instagram' => InstagramPublisher::class,
        'linkedin' => LinkedInPublisher::class,
        'pinterest' => PinterestPublisher::class,
        'tiktok' => TikTokPublisher::class,
    ];

    /**
     * Create a publisher instance from a platform enum.
     */
    public static function fromEnum(SocialPlatform $platform): PublisherInterface
    {
        return self::make($platform->value);
    }

    /**
     * Create a publisher instance from a platform string.
     */
    public static function make(string $platform): PublisherInterface
    {
        if (! self::isSupported($platform)) {
            throw new InvalidArgumentException("Unsupported publishing platform: {$platform}");
        }

        $class = self::$publishers[$platform];

        return app($class);
    }

    /**
     * Check if a platform is supported for publishing.
     */
    public static function isSupported(string $platform): bool
    {
        return isset(self::$publishers[$platform]);
    }

    /**
     * Get all supported platform identifiers.
     *
     * @return array<string>
     */
    public static function supportedPlatforms(): array
    {
        return array_keys(self::$publishers);
    }

    /**
     * Get all publisher instances.
     *
     * @return array<string, PublisherInterface>
     */
    public static function all(): array
    {
        $instances = [];

        foreach (self::$publishers as $platform => $class) {
            $instances[$platform] = app($class);
        }

        return $instances;
    }
}
