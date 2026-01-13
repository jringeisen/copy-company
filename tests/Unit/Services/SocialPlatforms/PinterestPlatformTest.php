<?php

use App\Services\SocialPlatforms\PinterestPlatform;
use App\Services\SocialPlatforms\PlatformInterface;

beforeEach(function () {
    $this->platform = new PinterestPlatform;
});

test('it implements platform interface', function () {
    expect($this->platform)->toBeInstanceOf(PlatformInterface::class);
});

test('it returns correct identifier', function () {
    expect($this->platform->getIdentifier())->toBe('pinterest');
});

test('it returns correct display name', function () {
    expect($this->platform->getDisplayName())->toBe('Pinterest');
});

test('it returns correct character limit', function () {
    expect($this->platform->getCharacterLimit())->toBe(500);
});

test('it returns ai prompt instructions', function () {
    $instructions = $this->platform->getAiPromptInstructions();

    expect($instructions)->toBeString()
        ->and($instructions)->toContain('Pinterest')
        ->and($instructions)->toContain('keywords');
});

test('it correctly identifies content within limit', function () {
    $shortContent = 'This is a short Pinterest pin description.';

    expect($this->platform->exceedsCharacterLimit($shortContent))->toBeFalse();
});

test('it correctly identifies content exceeding limit', function () {
    $longContent = str_repeat('a', 501);

    expect($this->platform->exceedsCharacterLimit($longContent))->toBeTrue();
});

test('it handles content at exact limit', function () {
    $exactContent = str_repeat('a', 500);

    expect($this->platform->exceedsCharacterLimit($exactContent))->toBeFalse();
});
