<?php

use App\Services\SocialPlatforms\LinkedInPlatform;
use App\Services\SocialPlatforms\PlatformInterface;

beforeEach(function () {
    $this->platform = new LinkedInPlatform;
});

test('it implements platform interface', function () {
    expect($this->platform)->toBeInstanceOf(PlatformInterface::class);
});

test('it returns correct identifier', function () {
    expect($this->platform->getIdentifier())->toBe('linkedin');
});

test('it returns correct display name', function () {
    expect($this->platform->getDisplayName())->toBe('LinkedIn');
});

test('it returns correct character limit', function () {
    expect($this->platform->getCharacterLimit())->toBe(3000);
});

test('it returns ai prompt instructions', function () {
    $instructions = $this->platform->getAiPromptInstructions();

    expect($instructions)->toBeString()
        ->and($instructions)->toContain('LinkedIn')
        ->and($instructions)->toContain('professional');
});

test('it correctly identifies content within limit', function () {
    $shortContent = 'This is a short LinkedIn post about professional matters.';

    expect($this->platform->exceedsCharacterLimit($shortContent))->toBeFalse();
});

test('it correctly identifies content exceeding limit', function () {
    $longContent = str_repeat('a', 3001);

    expect($this->platform->exceedsCharacterLimit($longContent))->toBeTrue();
});

test('it handles content at exact limit', function () {
    $exactContent = str_repeat('a', 3000);

    expect($this->platform->exceedsCharacterLimit($exactContent))->toBeFalse();
});
