<?php

use App\Models\Brand;
use App\Models\MarketingStrategy;
use App\Models\User;
use App\Services\AI\MarketingStrategyGenerator;
use App\Services\AI\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

uses(RefreshDatabase::class);

function createStrategyTextResponse(string $text): TextResponse
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

function sampleStrategyJson(): string
{
    return json_encode([
        'week_theme' => ['title' => 'Test Theme', 'description' => 'Test description'],
        'blog_posts' => [],
        'newsletter' => ['subject_line' => 'Test', 'topic' => 'Test topic', 'key_points' => [], 'suggested_day' => 'Thursday'],
        'social_posts' => [],
        'loop_content' => [],
        'talking_points' => ['Point 1'],
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create([
        'name' => 'Test Brand',
        'industry' => 'technology',
        'description' => 'A test brand',
        'tagline' => 'Test tagline',
    ]);
    $this->promptBuilder = new PromptBuilder;
    $this->generator = new MarketingStrategyGenerator($this->promptBuilder);
});

test('generate returns strategy and context', function () {
    Prism::fake([createStrategyTextResponse(sampleStrategyJson())]);

    $result = $this->generator->generate($this->brand);

    expect($result)->toHaveKeys(['strategy', 'context'])
        ->and($result['strategy']['week_theme']['title'])->toBe('Test Theme')
        ->and($result['context'])->toHaveKeys(['brand_name', 'industry', 'connected_platforms']);
});

test('context includes previous strategies', function () {
    MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create([
            'week_start' => now()->subWeek()->startOfWeek(),
            'week_end' => now()->subWeek()->endOfWeek(),
        ]);

    Prism::fake([createStrategyTextResponse(sampleStrategyJson())]);

    $result = $this->generator->generate($this->brand);

    expect($result['context']['previous_strategies'])->toHaveCount(1);
});

test('context includes recent content', function () {
    $this->brand->posts()->create([
        'user_id' => $this->user->id,
        'title' => 'Published Post',
        'slug' => 'published-post',
        'status' => 'published',
        'published_at' => now(),
        'content' => ['type' => 'doc', 'content' => []],
    ]);

    Prism::fake([createStrategyTextResponse(sampleStrategyJson())]);

    $result = $this->generator->generate($this->brand);

    expect($result['context']['recent_posts'])->toContain('Published Post');
});

test('context includes active loops', function () {
    $loop = $this->brand->loops()->create([
        'name' => 'Weekly Tips',
        'description' => 'Weekly tips loop',
        'is_active' => true,
        'platforms' => ['instagram'],
    ]);

    $loop->items()->create([
        'content' => 'Tip content',
        'position' => 0,
    ]);

    Prism::fake([createStrategyTextResponse(sampleStrategyJson())]);

    $result = $this->generator->generate($this->brand);

    expect($result['context']['active_loops'])->toHaveCount(1)
        ->and($result['context']['active_loops'][0]['name'])->toBe('Weekly Tips');
});

test('JSON parsing handles response with code blocks', function () {
    $wrappedJson = '```json
{"week_theme":{"title":"Theme","description":"Desc"},"blog_posts":[],"newsletter":{"subject_line":"Test","topic":"Test","key_points":[],"suggested_day":"Thursday"},"social_posts":[],"loop_content":[],"talking_points":[]}
```';

    Prism::fake([createStrategyTextResponse($wrappedJson)]);

    $result = $this->generator->generate($this->brand);

    expect($result['strategy']['week_theme']['title'])->toBe('Theme');
});

test('context includes strategy context', function () {
    $this->brand->update(['strategy_context' => 'We help small businesses automate their email marketing.']);

    Prism::fake([createStrategyTextResponse(sampleStrategyJson())]);

    $result = $this->generator->generate($this->brand);

    expect($result['context']['strategy_context'])->toBe('We help small businesses automate their email marketing.');
});

test('strategy context appears in the user prompt', function () {
    $this->brand->update(['strategy_context' => 'Our product solves scheduling pain points.']);

    $fake = Prism::fake([createStrategyTextResponse(sampleStrategyJson())]);

    $this->generator->generate($this->brand);

    $fake->assertRequest(function (array $recorded) {
        $prompt = $recorded[0]->prompt();
        expect($prompt)->toContain('MARKETING CONTEXT:')
            ->and($prompt)->toContain('Our product solves scheduling pain points.');
    });
});

test('context includes connected platforms', function () {
    $this->brand->social_connections = ['instagram' => ['token' => 'test'], 'facebook' => ['token' => 'test']];
    $this->brand->save();

    // Reload to get fresh data
    $this->brand->refresh();

    Prism::fake([createStrategyTextResponse(sampleStrategyJson())]);

    $result = $this->generator->generate($this->brand);

    expect($result['context']['connected_platforms'])->toContain('instagram')
        ->and($result['context']['connected_platforms'])->toContain('facebook');
});
