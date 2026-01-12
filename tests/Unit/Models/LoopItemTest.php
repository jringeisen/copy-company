<?php

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;
use App\Models\Loop;
use App\Models\LoopItem;
use App\Models\SocialPost;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('loop item belongs to a loop', function () {
    $item = LoopItem::factory()->create();

    expect($item->loop)->toBeInstanceOf(Loop::class);
});

test('loop item can belong to a social post', function () {
    $socialPost = SocialPost::factory()->create();
    $item = LoopItem::factory()->linkedToSocialPost($socialPost)->create();

    expect($item->socialPost)->toBeInstanceOf(SocialPost::class);
    expect($item->socialPost->id)->toBe($socialPost->id);
});

test('isLinked returns true when linked to social post', function () {
    $socialPost = SocialPost::factory()->create();
    $item = LoopItem::factory()->linkedToSocialPost($socialPost)->create();

    expect($item->isLinked())->toBeTrue();
});

test('isLinked returns false when standalone', function () {
    $item = LoopItem::factory()->create();

    expect($item->isLinked())->toBeFalse();
});

test('getPostContent returns content from linked social post', function () {
    $socialPost = SocialPost::factory()->create(['content' => 'Social post content']);
    $item = LoopItem::factory()->linkedToSocialPost($socialPost)->create();
    $item->load('socialPost');

    expect($item->getPostContent())->toBe('Social post content');
});

test('getPostContent returns standalone content when not linked', function () {
    $item = LoopItem::factory()->withContent('Standalone content')->create();

    expect($item->getPostContent())->toBe('Standalone content');
});

test('getPostHashtags returns hashtags from linked social post', function () {
    $socialPost = SocialPost::factory()->create(['hashtags' => ['tag1', 'tag2']]);
    $item = LoopItem::factory()->linkedToSocialPost($socialPost)->create();
    $item->load('socialPost');

    expect($item->getPostHashtags())->toBe(['tag1', 'tag2']);
});

test('getPostHashtags returns standalone hashtags when not linked', function () {
    $item = LoopItem::factory()->create(['hashtags' => ['standalone1', 'standalone2']]);

    expect($item->getPostHashtags())->toBe(['standalone1', 'standalone2']);
});

test('getPostLink returns link from linked social post', function () {
    $socialPost = SocialPost::factory()->create(['link' => 'https://example.com']);
    $item = LoopItem::factory()->linkedToSocialPost($socialPost)->create();
    $item->load('socialPost');

    expect($item->getPostLink())->toBe('https://example.com');
});

test('getPostLink returns standalone link when not linked', function () {
    $item = LoopItem::factory()->create(['link' => 'https://standalone.com']);

    expect($item->getPostLink())->toBe('https://standalone.com');
});

test('getPostMedia returns media from linked social post', function () {
    $socialPost = SocialPost::factory()->create(['media' => [1, 2, 3]]);
    $item = LoopItem::factory()->linkedToSocialPost($socialPost)->create();
    $item->load('socialPost');

    expect($item->getPostMedia())->toBe([1, 2, 3]);
});

test('getPostMedia returns standalone media when not linked', function () {
    $item = LoopItem::factory()->withMedia()->create();

    expect($item->getPostMedia())->toBe([1, 2]);
});

test('getPostFormat returns format from linked social post', function () {
    $socialPost = SocialPost::factory()->create(['format' => SocialFormat::Story]);
    $item = LoopItem::factory()->linkedToSocialPost($socialPost)->create();
    $item->load('socialPost');

    expect($item->getPostFormat())->toBe(SocialFormat::Story);
});

test('getPostFormat returns standalone format when not linked', function () {
    $item = LoopItem::factory()->create(['format' => SocialFormat::Reel]);

    expect($item->getPostFormat())->toBe(SocialFormat::Reel);
});

test('getPostFormat returns Feed when linked social post has Feed format', function () {
    $socialPost = SocialPost::factory()->create(['format' => SocialFormat::Feed]);
    $item = LoopItem::factory()->linkedToSocialPost($socialPost)->create();
    $item->load('socialPost');

    expect($item->getPostFormat())->toBe(SocialFormat::Feed);
});

test('hasMedia returns true when item has media', function () {
    $item = LoopItem::factory()->withMedia()->create();

    expect($item->hasMedia())->toBeTrue();
});

test('hasMedia returns false when item has no media', function () {
    $item = LoopItem::factory()->create(['media' => []]);

    expect($item->hasMedia())->toBeFalse();
});

test('recordPosted increments times_posted and sets last_posted_at', function () {
    $item = LoopItem::factory()->create(['times_posted' => 0, 'last_posted_at' => null]);

    $item->recordPosted();
    $item->refresh();

    expect($item->times_posted)->toBe(1);
    expect($item->last_posted_at)->not->toBeNull();
});

test('recordPosted can be called multiple times', function () {
    $item = LoopItem::factory()->create(['times_posted' => 5]);

    $item->recordPosted();
    $item->refresh();

    expect($item->times_posted)->toBe(6);
});

test('meetsRequirementsFor returns false when platform requires media and item has none', function () {
    $item = LoopItem::factory()->create(['media' => []]);

    // Instagram requires media
    expect($item->meetsRequirementsFor(SocialPlatform::Instagram))->toBeFalse();
});

test('meetsRequirementsFor returns true when platform requires media and item has media', function () {
    $item = LoopItem::factory()->withMedia()->create();

    expect($item->meetsRequirementsFor(SocialPlatform::Instagram))->toBeTrue();
});

test('meetsRequirementsFor returns false for TikTok because video is required', function () {
    $item = LoopItem::factory()->withMedia()->create();

    // TikTok requires video which is not currently supported
    expect($item->meetsRequirementsFor(SocialPlatform::TikTok))->toBeFalse();
});

test('meetsRequirementsFor returns false when content exceeds platform character limit', function () {
    // Create content that exceeds LinkedIn's 3000 character limit
    $longContent = str_repeat('a', 3100);
    $item = LoopItem::factory()->withContent($longContent)->create();

    expect($item->meetsRequirementsFor(SocialPlatform::LinkedIn))->toBeFalse();
});

test('meetsRequirementsFor returns true when content is within platform character limit', function () {
    $shortContent = 'Short tweet content';
    $item = LoopItem::factory()->withContent($shortContent)->create();

    expect($item->meetsRequirementsFor(SocialPlatform::LinkedIn))->toBeTrue();
});

test('getQualifiedPlatforms returns empty array when loop not loaded', function () {
    $item = LoopItem::factory()->create();

    expect($item->getQualifiedPlatforms())->toBe([]);
});

test('getQualifiedPlatforms returns platforms item qualifies for', function () {
    $loop = Loop::factory()->create(['platforms' => ['linkedin', 'facebook']]);
    $item = LoopItem::factory()->forLoop($loop)->withContent('Short content')->create();
    $item->setRelation('loop', $loop);

    $qualified = $item->getQualifiedPlatforms();

    expect($qualified)->toContain('linkedin');
    expect($qualified)->toContain('facebook');
});

test('getQualifiedPlatforms excludes platforms item does not qualify for', function () {
    $loop = Loop::factory()->create(['platforms' => ['linkedin', 'instagram']]);
    // No media, so won't qualify for Instagram
    $item = LoopItem::factory()->forLoop($loop)->withContent('Short content')->create(['media' => []]);
    $item->setRelation('loop', $loop);

    $qualified = $item->getQualifiedPlatforms();

    expect($qualified)->toContain('linkedin');
    expect($qualified)->not->toContain('instagram');
});

test('getDisqualifiedPlatforms returns empty array when loop not loaded', function () {
    $item = LoopItem::factory()->create();

    expect($item->getDisqualifiedPlatforms())->toBe([]);
});

test('getDisqualifiedPlatforms returns platforms with reasons', function () {
    $loop = Loop::factory()->create(['platforms' => ['instagram']]);
    // No media, so won't qualify for Instagram
    $item = LoopItem::factory()->forLoop($loop)->create(['media' => []]);
    $item->setRelation('loop', $loop);

    $disqualified = $item->getDisqualifiedPlatforms();

    expect($disqualified)->toHaveKey('instagram');
    expect($disqualified['instagram'])->toBeString();
});

test('position is cast as integer', function () {
    $item = LoopItem::factory()->atPosition(5)->create();

    expect($item->position)->toBeInt();
    expect($item->position)->toBe(5);
});

test('times_posted is cast as integer', function () {
    $item = LoopItem::factory()->withTimesPosted(10)->create();

    expect($item->times_posted)->toBeInt();
    expect($item->times_posted)->toBe(10);
});

test('last_posted_at is cast as datetime', function () {
    $item = LoopItem::factory()->withTimesPosted(1)->create();

    expect($item->last_posted_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
