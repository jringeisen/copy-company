<?php

use App\Services\SocialPlatforms\PlatformInterface;
use App\Services\SocialPlatforms\TikTokPlatform;

beforeEach(function () {
    $this->platform = new TikTokPlatform;
});

test('it implements platform interface', function () {
    expect($this->platform)->toBeInstanceOf(PlatformInterface::class);
});

test('it returns correct identifier', function () {
    expect($this->platform->getIdentifier())->toBe('tiktok');
});

test('it returns correct display name', function () {
    expect($this->platform->getDisplayName())->toBe('TikTok');
});

test('it returns correct character limit', function () {
    expect($this->platform->getCharacterLimit())->toBe(2200);
});

test('it returns ai prompt instructions', function () {
    $instructions = $this->platform->getAiPromptInstructions();

    expect($instructions)->toBeString()
        ->and($instructions)->toContain('TikTok')
        ->and($instructions)->toContain('hashtags');
});

test('it correctly identifies content within limit', function () {
    $shortContent = 'This is a short TikTok caption.';

    expect($this->platform->exceedsCharacterLimit($shortContent))->toBeFalse();
});

test('it correctly identifies content exceeding limit', function () {
    $longContent = str_repeat('a', 2201);

    expect($this->platform->exceedsCharacterLimit($longContent))->toBeTrue();
});

test('it handles content at exact limit', function () {
    $exactContent = str_repeat('a', 2200);

    expect($this->platform->exceedsCharacterLimit($exactContent))->toBeFalse();
});
