<?php

use App\Enums\SocialPlatform;
use App\Services\SocialPlatforms\FacebookPlatform;
use App\Services\SocialPlatforms\InstagramPlatform;
use App\Services\SocialPlatforms\LinkedInPlatform;
use App\Services\SocialPlatforms\PinterestPlatform;
use App\Services\SocialPlatforms\PlatformFactory;
use App\Services\SocialPlatforms\PlatformInterface;
use App\Services\SocialPlatforms\TikTokPlatform;

test('it creates instagram platform from string', function () {
    $platform = PlatformFactory::make('instagram');

    expect($platform)->toBeInstanceOf(InstagramPlatform::class)
        ->and($platform)->toBeInstanceOf(PlatformInterface::class);
});

test('it creates facebook platform from string', function () {
    $platform = PlatformFactory::make('facebook');

    expect($platform)->toBeInstanceOf(FacebookPlatform::class);
});

test('it creates linkedin platform from string', function () {
    $platform = PlatformFactory::make('linkedin');

    expect($platform)->toBeInstanceOf(LinkedInPlatform::class);
});

test('it creates pinterest platform from string', function () {
    $platform = PlatformFactory::make('pinterest');

    expect($platform)->toBeInstanceOf(PinterestPlatform::class);
});

test('it creates tiktok platform from string', function () {
    $platform = PlatformFactory::make('tiktok');

    expect($platform)->toBeInstanceOf(TikTokPlatform::class);
});

test('it throws exception for unknown platform', function () {
    PlatformFactory::make('unknown');
})->throws(InvalidArgumentException::class, 'Unknown platform: unknown');

test('it creates platform from enum', function () {
    $platform = PlatformFactory::fromEnum(SocialPlatform::Instagram);

    expect($platform)->toBeInstanceOf(InstagramPlatform::class);
});

test('it creates facebook platform from enum', function () {
    $platform = PlatformFactory::fromEnum(SocialPlatform::Facebook);

    expect($platform)->toBeInstanceOf(FacebookPlatform::class);
});

test('it creates linkedin platform from enum', function () {
    $platform = PlatformFactory::fromEnum(SocialPlatform::LinkedIn);

    expect($platform)->toBeInstanceOf(LinkedInPlatform::class);
});

test('it creates pinterest platform from enum', function () {
    $platform = PlatformFactory::fromEnum(SocialPlatform::Pinterest);

    expect($platform)->toBeInstanceOf(PinterestPlatform::class);
});

test('it creates tiktok platform from enum', function () {
    $platform = PlatformFactory::fromEnum(SocialPlatform::TikTok);

    expect($platform)->toBeInstanceOf(TikTokPlatform::class);
});

test('it checks if platform is supported', function () {
    expect(PlatformFactory::isSupported('instagram'))->toBeTrue()
        ->and(PlatformFactory::isSupported('facebook'))->toBeTrue()
        ->and(PlatformFactory::isSupported('linkedin'))->toBeTrue()
        ->and(PlatformFactory::isSupported('pinterest'))->toBeTrue()
        ->and(PlatformFactory::isSupported('tiktok'))->toBeTrue()
        ->and(PlatformFactory::isSupported('unknown'))->toBeFalse()
        ->and(PlatformFactory::isSupported('twitter'))->toBeFalse();
});

test('it returns all platform instances', function () {
    $platforms = PlatformFactory::all();

    expect($platforms)->toBeArray()
        ->and($platforms)->toHaveCount(5)
        ->and($platforms['instagram'])->toBeInstanceOf(InstagramPlatform::class)
        ->and($platforms['facebook'])->toBeInstanceOf(FacebookPlatform::class)
        ->and($platforms['linkedin'])->toBeInstanceOf(LinkedInPlatform::class)
        ->and($platforms['pinterest'])->toBeInstanceOf(PinterestPlatform::class)
        ->and($platforms['tiktok'])->toBeInstanceOf(TikTokPlatform::class);
});

test('it returns all supported platform identifiers', function () {
    $identifiers = PlatformFactory::supportedIdentifiers();

    expect($identifiers)->toBeArray()
        ->and($identifiers)->toContain('instagram')
        ->and($identifiers)->toContain('facebook')
        ->and($identifiers)->toContain('linkedin')
        ->and($identifiers)->toContain('pinterest')
        ->and($identifiers)->toContain('tiktok')
        ->and($identifiers)->toHaveCount(5);
});
