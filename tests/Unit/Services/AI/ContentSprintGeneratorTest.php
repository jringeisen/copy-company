<?php

use App\Models\Brand;
use App\Models\User;
use App\Services\AI\ContentSprintGenerator;
use App\Services\AI\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

uses(RefreshDatabase::class);

function createSprintTextResponse(string $text): TextResponse
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
    $this->promptBuilder = new PromptBuilder;
    $this->generator = new ContentSprintGenerator($this->promptBuilder);
});

test('generate returns parsed ideas from Prism response', function () {
    $validJson = json_encode([
        [
            'title' => 'Test Blog Post',
            'description' => 'A great test post',
            'key_points' => ['Point 1', 'Point 2'],
            'estimated_words' => 1000,
        ],
    ]);

    Prism::fake([createSprintTextResponse($validJson)]);

    $result = $this->generator->generate($this->brand, ['topic1', 'topic2'], 'goals', 5);

    expect($result)->toBeArray()
        ->and($result[0]['title'])->toBe('Test Blog Post');
});

test('generate parses multiple ideas', function () {
    $validJson = json_encode([
        [
            'title' => 'Test Post',
            'description' => 'Test description',
            'key_points' => ['Point 1', 'Point 2', 'Point 3'],
            'estimated_words' => 1200,
        ],
        [
            'title' => 'Another Post',
            'description' => 'Another description',
            'key_points' => ['A', 'B'],
            'estimated_words' => 800,
        ],
    ]);

    Prism::fake([createSprintTextResponse($validJson)]);

    $result = $this->generator->generate($this->brand, ['test'], '', 2);

    expect($result)->toHaveCount(2)
        ->and($result[0]['title'])->toBe('Test Post')
        ->and($result[1]['title'])->toBe('Another Post');
});

test('generate cleans markdown code blocks from response', function () {
    $jsonWithMarkdown = "```json\n".json_encode([
        ['title' => 'Test', 'description' => 'Test', 'key_points' => [], 'estimated_words' => 500],
    ])."\n```";

    Prism::fake([createSprintTextResponse($jsonWithMarkdown)]);

    $result = $this->generator->generate($this->brand, ['test'], '', 1);

    expect($result)->toBeArray()
        ->and($result[0]['title'])->toBe('Test');
});

test('generate throws exception for invalid JSON response', function () {
    Log::shouldReceive('error')->once();

    Prism::fake([createSprintTextResponse('not valid json')]);

    expect(fn () => $this->generator->generate($this->brand, ['test'], '', 1))
        ->toThrow(Exception::class, 'Failed to parse AI response as JSON');
});

test('generate returns ideas with all expected fields', function () {
    $validJson = json_encode([
        [
            'title' => 'Complete Post',
            'description' => 'Full description',
            'key_points' => ['Point A', 'Point B', 'Point C'],
            'estimated_words' => 1500,
        ],
    ]);

    Prism::fake([createSprintTextResponse($validJson)]);

    $result = $this->generator->generate($this->brand, ['test']);

    expect($result[0])->toHaveKeys(['title', 'description', 'key_points', 'estimated_words'])
        ->and($result[0]['title'])->toBe('Complete Post')
        ->and($result[0]['description'])->toBe('Full description')
        ->and($result[0]['key_points'])->toBe(['Point A', 'Point B', 'Point C'])
        ->and($result[0]['estimated_words'])->toBe(1500);
});

test('generate can be instantiated via container', function () {
    $generator = app(ContentSprintGenerator::class);

    expect($generator)->toBeInstanceOf(ContentSprintGenerator::class);
});
