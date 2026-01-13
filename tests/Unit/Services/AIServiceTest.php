<?php

use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use App\Services\AI\BlogContentGenerator;
use App\Services\AI\ContentSprintGenerator;
use App\Services\AI\SocialContentGenerator;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create([
        'name' => 'Test Brand',
        'industry' => 'Technology',
    ]);

    $this->blogGenerator = Mockery::mock(BlogContentGenerator::class);
    $this->socialGenerator = Mockery::mock(SocialContentGenerator::class);
    $this->sprintGenerator = Mockery::mock(ContentSprintGenerator::class);

    $this->aiService = new AIService(
        $this->blogGenerator,
        $this->socialGenerator,
        $this->sprintGenerator
    );
});

test('generateDraft delegates to blog generator', function () {
    $this->blogGenerator
        ->shouldReceive('generateDraft')
        ->with($this->brand, 'Test Title', 'Some bullets')
        ->once()
        ->andReturn('Generated draft content');

    $result = $this->aiService->generateDraft($this->brand, 'Test Title', 'Some bullets');

    expect($result)->toBe('Generated draft content');
});

test('generateDraft passes null bullets when not provided', function () {
    $this->blogGenerator
        ->shouldReceive('generateDraft')
        ->with($this->brand, 'Test Title', null)
        ->once()
        ->andReturn('Generated draft');

    $result = $this->aiService->generateDraft($this->brand, 'Test Title');

    expect($result)->toBe('Generated draft');
});

test('polishWriting delegates to blog generator', function () {
    $this->blogGenerator
        ->shouldReceive('polishWriting')
        ->with($this->brand, 'Content to polish')
        ->once()
        ->andReturn('Polished content');

    $result = $this->aiService->polishWriting($this->brand, 'Content to polish');

    expect($result)->toBe('Polished content');
});

test('continueWriting delegates to blog generator', function () {
    $this->blogGenerator
        ->shouldReceive('continueWriting')
        ->with($this->brand, 'Content so far...')
        ->once()
        ->andReturn('Continued content');

    $result = $this->aiService->continueWriting($this->brand, 'Content so far...');

    expect($result)->toBe('Continued content');
});

test('suggestOutline delegates to blog generator', function () {
    $this->blogGenerator
        ->shouldReceive('suggestOutline')
        ->with($this->brand, 'Post Title', 'Some notes')
        ->once()
        ->andReturn('Suggested outline');

    $result = $this->aiService->suggestOutline($this->brand, 'Post Title', 'Some notes');

    expect($result)->toBe('Suggested outline');
});

test('suggestOutline passes null notes when not provided', function () {
    $this->blogGenerator
        ->shouldReceive('suggestOutline')
        ->with($this->brand, 'Post Title', null)
        ->once()
        ->andReturn('Outline');

    $result = $this->aiService->suggestOutline($this->brand, 'Post Title');

    expect($result)->toBe('Outline');
});

test('changeTone delegates to blog generator', function () {
    $this->blogGenerator
        ->shouldReceive('changeTone')
        ->with($this->brand, 'Original content', 'professional')
        ->once()
        ->andReturn('Professional content');

    $result = $this->aiService->changeTone($this->brand, 'Original content', 'professional');

    expect($result)->toBe('Professional content');
});

test('makeItShorter delegates to blog generator', function () {
    $this->blogGenerator
        ->shouldReceive('makeItShorter')
        ->with('Long content that needs shortening')
        ->once()
        ->andReturn('Short content');

    $result = $this->aiService->makeItShorter($this->brand, 'Long content that needs shortening');

    expect($result)->toBe('Short content');
});

test('makeItLonger delegates to blog generator', function () {
    $this->blogGenerator
        ->shouldReceive('makeItLonger')
        ->with($this->brand, 'Short content')
        ->once()
        ->andReturn('Longer content with more details');

    $result = $this->aiService->makeItLonger($this->brand, 'Short content');

    expect($result)->toBe('Longer content with more details');
});

test('freeformQuestion delegates to blog generator', function () {
    $this->blogGenerator
        ->shouldReceive('freeformQuestion')
        ->with($this->brand, 'Content context', 'How can I improve this?')
        ->once()
        ->andReturn('Improvement suggestions');

    $result = $this->aiService->freeformQuestion($this->brand, 'Content context', 'How can I improve this?');

    expect($result)->toBe('Improvement suggestions');
});

test('atomizeToSocial delegates to social generator', function () {
    $post = Post::factory()->forBrand($this->brand)->create();
    $platforms = ['facebook', 'instagram'];

    $expectedResult = [
        'facebook' => ['platform' => 'facebook', 'content' => 'FB post'],
        'instagram' => ['platform' => 'instagram', 'content' => 'IG post'],
    ];

    $this->socialGenerator
        ->shouldReceive('atomize')
        ->with($this->brand, $post, $platforms)
        ->once()
        ->andReturn($expectedResult);

    $result = $this->aiService->atomizeToSocial($this->brand, $post, $platforms);

    expect($result)->toBe($expectedResult);
});

test('atomizeToSocial passes empty array when no platforms', function () {
    $post = Post::factory()->forBrand($this->brand)->create();

    $this->socialGenerator
        ->shouldReceive('atomize')
        ->with($this->brand, $post, [])
        ->once()
        ->andReturn([]);

    $result = $this->aiService->atomizeToSocial($this->brand, $post, []);

    expect($result)->toBe([]);
});

test('generateContentSprintIdeas delegates to sprint generator', function () {
    $topics = ['marketing', 'seo'];
    $goals = 'Increase engagement';
    $count = 10;

    $expectedResult = [
        ['title' => 'Post 1', 'description' => 'Description 1'],
        ['title' => 'Post 2', 'description' => 'Description 2'],
    ];

    $this->sprintGenerator
        ->shouldReceive('generate')
        ->with($this->brand, $topics, $goals, $count)
        ->once()
        ->andReturn($expectedResult);

    $result = $this->aiService->generateContentSprintIdeas($this->brand, $topics, $goals, $count);

    expect($result)->toBe($expectedResult);
});

test('generateContentSprintIdeas uses default values', function () {
    $topics = ['tech'];

    $this->sprintGenerator
        ->shouldReceive('generate')
        ->with($this->brand, $topics, '', 20)
        ->once()
        ->andReturn([]);

    $result = $this->aiService->generateContentSprintIdeas($this->brand, $topics);

    expect($result)->toBe([]);
});

test('generateContentSprintIdeas passes custom goals', function () {
    $topics = ['content'];
    $goals = 'Drive traffic';

    $this->sprintGenerator
        ->shouldReceive('generate')
        ->with($this->brand, $topics, $goals, 20)
        ->once()
        ->andReturn([]);

    $result = $this->aiService->generateContentSprintIdeas($this->brand, $topics, $goals);

    expect($result)->toBe([]);
});

test('generateContentSprintIdeas passes custom count', function () {
    $topics = ['blog'];
    $count = 5;

    $this->sprintGenerator
        ->shouldReceive('generate')
        ->with($this->brand, $topics, '', $count)
        ->once()
        ->andReturn([]);

    $result = $this->aiService->generateContentSprintIdeas($this->brand, $topics, '', $count);

    expect($result)->toBe([]);
});

test('service can be instantiated via container', function () {
    $service = app(AIService::class);

    expect($service)->toBeInstanceOf(AIService::class);
});
