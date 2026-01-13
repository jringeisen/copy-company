<?php

use App\Services\SocialPlatforms\FacebookPlatform;
use App\Services\SocialPlatforms\PlatformInterface;

beforeEach(function () {
    $this->platform = new FacebookPlatform;
});

test('it implements platform interface', function () {
    expect($this->platform)->toBeInstanceOf(PlatformInterface::class);
});

test('it returns correct identifier', function () {
    expect($this->platform->getIdentifier())->toBe('facebook');
});

test('it returns correct display name', function () {
    expect($this->platform->getDisplayName())->toBe('Facebook');
});

test('it returns correct character limit', function () {
    expect($this->platform->getCharacterLimit())->toBe(63206);
});

test('it returns ai prompt instructions', function () {
    $instructions = $this->platform->getAiPromptInstructions();

    expect($instructions)->toBeString()
        ->and($instructions)->toContain('Facebook')
        ->and($instructions)->toContain('engagement');
});

test('it correctly identifies content within limit', function () {
    $shortContent = 'This is a short Facebook post.';

    expect($this->platform->exceedsCharacterLimit($shortContent))->toBeFalse();
});

test('it correctly identifies content exceeding limit', function () {
    $longContent = str_repeat('a', 63207);

    expect($this->platform->exceedsCharacterLimit($longContent))->toBeTrue();
});

test('it handles content at exact limit', function () {
    $exactContent = str_repeat('a', 63206);

    expect($this->platform->exceedsCharacterLimit($exactContent))->toBeFalse();
});
