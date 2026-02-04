<?php

use App\Enums\MarketingStrategyStatus;
use App\Jobs\GenerateMarketingStrategy;
use App\Models\Brand;
use App\Models\MarketingStrategy;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('it generates marketing strategy', function () {
    $strategy = MarketingStrategy::factory()->forBrand($this->brand)->create([
        'status' => MarketingStrategyStatus::Pending,
    ]);

    $mockResult = [
        'strategy' => [
            'week_theme' => ['title' => 'Test Theme', 'description' => 'Test description'],
            'blog_posts' => [['title' => 'Blog Post 1']],
            'newsletter' => ['subject_line' => 'Newsletter Subject'],
            'social_posts' => [['platform' => 'instagram', 'content' => 'Test content']],
            'loops' => [],
            'talking_points' => ['Point 1'],
        ],
        'context' => ['brand_name' => $this->brand->name],
    ];

    $this->mock(AIService::class, function (MockInterface $mock) use ($mockResult) {
        $mock->shouldReceive('generateMarketingStrategy')
            ->once()
            ->with(\Mockery::type(Brand::class))
            ->andReturn($mockResult);
    });

    $job = new GenerateMarketingStrategy($strategy);
    $job->handle(app(AIService::class));

    $strategy->refresh();

    expect($strategy->status)->toBe(MarketingStrategyStatus::Completed)
        ->and($strategy->strategy_content)->not->toBeNull()
        ->and($strategy->strategy_content['week_theme']['title'])->toBe('Test Theme')
        ->and($strategy->context_snapshot)->not->toBeNull()
        ->and($strategy->completed_at)->not->toBeNull();
});

test('it sets generating status during execution', function () {
    $strategy = MarketingStrategy::factory()->forBrand($this->brand)->create([
        'status' => MarketingStrategyStatus::Pending,
    ]);

    $this->mock(AIService::class, function (MockInterface $mock) use ($strategy) {
        $mock->shouldReceive('generateMarketingStrategy')
            ->once()
            ->andReturnUsing(function () use ($strategy) {
                // At this point, the status should be Generating
                $strategy->refresh();
                expect($strategy->status)->toBe(MarketingStrategyStatus::Generating);

                return [
                    'strategy' => ['week_theme' => ['title' => 'Test']],
                    'context' => [],
                ];
            });
    });

    $job = new GenerateMarketingStrategy($strategy);
    $job->handle(app(AIService::class));
});

test('it handles failure correctly', function () {
    $strategy = MarketingStrategy::factory()->forBrand($this->brand)->create([
        'status' => MarketingStrategyStatus::Pending,
    ]);

    $this->mock(AIService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateMarketingStrategy')
            ->once()
            ->andThrow(new \Exception('AI service error'));
    });

    $job = new GenerateMarketingStrategy($strategy);

    expect(fn () => $job->handle(app(AIService::class)))->toThrow(\Exception::class);

    $strategy->refresh();
    expect($strategy->status)->toBe(MarketingStrategyStatus::Failed);
});

test('job has correct configuration', function () {
    $strategy = MarketingStrategy::factory()->forBrand($this->brand)->create();
    $job = new GenerateMarketingStrategy($strategy);

    expect($job->timeout)->toBe(300)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([30, 120]);
});

test('failed method sets status to failed', function () {
    $strategy = MarketingStrategy::factory()->forBrand($this->brand)->create([
        'status' => MarketingStrategyStatus::Generating,
    ]);

    $job = new GenerateMarketingStrategy($strategy);
    $job->failed(new \Exception('Permanent failure'));

    $strategy->refresh();
    expect($strategy->status)->toBe(MarketingStrategyStatus::Failed);
});
