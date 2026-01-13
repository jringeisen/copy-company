<?php

use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\PromptBuilder;
use App\Services\AI\SocialContentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

uses(RefreshDatabase::class);

function createSocialTextResponse(string $text): TextResponse
{
    return new TextResponse(
        steps: new Collection,
        text: $text,
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(0, 0),
        meta: new Meta('test-id', 'test-model'),
        messages: new Collection,
    );
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create([
        'name' => 'Test Brand',
        'industry' => 'Technology',
    ]);
    $this->post = Post::factory()->forBrand($this->brand)->create([
        'title' => 'Test Blog Post',
        'content_html' => '<p>This is the blog post content for testing.</p>',
    ]);
    $this->promptBuilder = new PromptBuilder;
    $this->generator = new SocialContentGenerator($this->promptBuilder);
});

test('atomize returns empty array when no platforms provided', function () {
    $result = $this->generator->atomize($this->brand, $this->post, []);

    expect($result)->toBe([]);
});

test('atomize skips unsupported platforms', function () {
    $content = 'Generated Facebook content #test';

    Prism::fake([createSocialTextResponse($content)]);

    $result = $this->generator->atomize($this->brand, $this->post, ['facebook', 'unknown_platform']);

    expect($result)->toHaveKey('facebook')
        ->and($result)->not->toHaveKey('unknown_platform');
});

test('atomize generates content for single platform', function () {
    $content = 'Check out our latest blog post! #tech #blog';

    Prism::fake([createSocialTextResponse($content)]);

    $result = $this->generator->atomize($this->brand, $this->post, ['facebook']);

    expect($result)->toHaveKey('facebook')
        ->and($result['facebook']['platform'])->toBe('facebook')
        ->and($result['facebook']['content'])->toBe($content)
        ->and($result['facebook']['hashtags'])->toBe(['tech', 'blog'])
        ->and($result['facebook']['character_count'])->toBe(strlen($content));
});

test('atomize generates content for multiple platforms', function () {
    Prism::fake([
        createSocialTextResponse('Facebook content #fb'),
        createSocialTextResponse('Instagram content #ig'),
        createSocialTextResponse('LinkedIn content #li'),
    ]);

    $result = $this->generator->atomize($this->brand, $this->post, ['facebook', 'instagram', 'linkedin']);

    expect($result)->toHaveKey('facebook')
        ->and($result)->toHaveKey('instagram')
        ->and($result)->toHaveKey('linkedin');
});

test('extractHashtags extracts hashtags from content', function () {
    $content = 'Great post! #marketing #socialmedia #tech';

    Prism::fake([createSocialTextResponse($content)]);

    $result = $this->generator->atomize($this->brand, $this->post, ['facebook']);

    expect($result['facebook']['hashtags'])->toBe(['marketing', 'socialmedia', 'tech']);
});

test('extractHashtags returns empty array when no hashtags', function () {
    $content = 'Great post without any hashtags!';

    Prism::fake([createSocialTextResponse($content)]);

    $result = $this->generator->atomize($this->brand, $this->post, ['facebook']);

    expect($result['facebook']['hashtags'])->toBe([]);
});

test('result includes character limit from platform', function () {
    Prism::fake([createSocialTextResponse('Test content')]);

    $result = $this->generator->atomize($this->brand, $this->post, ['facebook']);

    expect($result['facebook'])->toHaveKey('character_limit')
        ->and($result['facebook']['character_limit'])->toBeInt();
});

test('atomize handles all supported platforms', function () {
    Prism::fake([
        createSocialTextResponse('Facebook content #test'),
        createSocialTextResponse('Instagram content #test'),
        createSocialTextResponse('LinkedIn content #test'),
        createSocialTextResponse('Pinterest content #test'),
        createSocialTextResponse('TikTok content #test'),
    ]);

    $platforms = ['facebook', 'instagram', 'linkedin', 'pinterest', 'tiktok'];
    $result = $this->generator->atomize($this->brand, $this->post, $platforms);

    foreach ($platforms as $platform) {
        expect($result)->toHaveKey($platform)
            ->and($result[$platform]['platform'])->toBe($platform);
    }
});

test('atomize can be instantiated via container', function () {
    $generator = app(SocialContentGenerator::class);

    expect($generator)->toBeInstanceOf(SocialContentGenerator::class);
});

test('result contains platform identifier', function () {
    Prism::fake([createSocialTextResponse('Test content')]);

    $result = $this->generator->atomize($this->brand, $this->post, ['instagram']);

    expect($result['instagram']['platform'])->toBe('instagram');
});

test('result contains character count', function () {
    $content = 'This is a test post';

    Prism::fake([createSocialTextResponse($content)]);

    $result = $this->generator->atomize($this->brand, $this->post, ['facebook']);

    expect($result['facebook']['character_count'])->toBe(strlen($content));
});
