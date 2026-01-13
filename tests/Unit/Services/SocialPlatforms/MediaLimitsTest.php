<?php

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;
use App\Services\SocialPlatforms\MediaLimits;

test('it gets correct limit for instagram feed', function () {
    expect(MediaLimits::getLimit(SocialPlatform::Instagram, SocialFormat::Feed))->toBe(10);
});

test('it gets correct limit for instagram story', function () {
    expect(MediaLimits::getLimit(SocialPlatform::Instagram, SocialFormat::Story))->toBe(1);
});

test('it gets correct limit for instagram reel (video only)', function () {
    expect(MediaLimits::getLimit(SocialPlatform::Instagram, SocialFormat::Reel))->toBe(0);
});

test('it gets correct limit for facebook feed', function () {
    expect(MediaLimits::getLimit(SocialPlatform::Facebook, SocialFormat::Feed))->toBe(10);
});

test('it gets correct limit for linkedin feed', function () {
    expect(MediaLimits::getLimit(SocialPlatform::LinkedIn, SocialFormat::Feed))->toBe(9);
});

test('it gets correct limit for pinterest pin', function () {
    expect(MediaLimits::getLimit(SocialPlatform::Pinterest, SocialFormat::Pin))->toBe(5);
});

test('it gets correct limit for tiktok feed (video only)', function () {
    expect(MediaLimits::getLimit(SocialPlatform::TikTok, SocialFormat::Feed))->toBe(0);
});

test('it returns 0 for unknown format', function () {
    expect(MediaLimits::getLimit('instagram', 'unknown'))->toBe(0);
});

test('it returns 0 for unknown platform', function () {
    expect(MediaLimits::getLimit('unknown', 'feed'))->toBe(0);
});

test('it accepts string values', function () {
    expect(MediaLimits::getLimit('instagram', 'feed'))->toBe(10);
});

test('it checks if platform allows images', function () {
    expect(MediaLimits::allowsImages(SocialPlatform::Instagram, SocialFormat::Feed))->toBeTrue()
        ->and(MediaLimits::allowsImages(SocialPlatform::Instagram, SocialFormat::Reel))->toBeFalse()
        ->and(MediaLimits::allowsImages(SocialPlatform::TikTok, SocialFormat::Feed))->toBeFalse();
});

test('it validates media count within limit', function () {
    expect(MediaLimits::validateMediaCount(SocialPlatform::Instagram, SocialFormat::Feed, [1, 2, 3]))->toBeTrue()
        ->and(MediaLimits::validateMediaCount(SocialPlatform::Instagram, SocialFormat::Feed, range(1, 10)))->toBeTrue()
        ->and(MediaLimits::validateMediaCount(SocialPlatform::Instagram, SocialFormat::Feed, range(1, 11)))->toBeFalse();
});

test('it validates media count for story (single image)', function () {
    expect(MediaLimits::validateMediaCount(SocialPlatform::Instagram, SocialFormat::Story, [1]))->toBeTrue()
        ->and(MediaLimits::validateMediaCount(SocialPlatform::Instagram, SocialFormat::Story, [1, 2]))->toBeFalse();
});

test('it gets limits for platform', function () {
    $instagramLimits = MediaLimits::getLimitsForPlatform(SocialPlatform::Instagram);

    expect($instagramLimits)->toBeArray()
        ->and($instagramLimits['feed'])->toBe(10)
        ->and($instagramLimits['story'])->toBe(1)
        ->and($instagramLimits['reel'])->toBe(0);
});

test('it returns empty array for unknown platform', function () {
    expect(MediaLimits::getLimitsForPlatform('unknown'))->toBe([]);
});

test('it gets image formats for platform', function () {
    $formats = MediaLimits::getImageFormatsForPlatform(SocialPlatform::Instagram);

    expect($formats)->toContain('feed', 'story', 'carousel')
        ->and($formats)->not->toContain('reel');
});

test('it gets limit message for multiple images', function () {
    $message = MediaLimits::getLimitMessage(SocialPlatform::Instagram, SocialFormat::Feed);

    expect($message)->toBe('You can attach up to 10 images.');
});

test('it gets limit message for single image', function () {
    $message = MediaLimits::getLimitMessage(SocialPlatform::Instagram, SocialFormat::Story);

    expect($message)->toBe('You can attach 1 image.');
});

test('it gets limit message for video only', function () {
    $message = MediaLimits::getLimitMessage(SocialPlatform::Instagram, SocialFormat::Reel);

    expect($message)->toBe('This format does not support images (video only).');
});
