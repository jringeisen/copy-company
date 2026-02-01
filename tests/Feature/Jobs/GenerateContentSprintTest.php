<?php

use App\Enums\ContentSprintStatus;
use App\Jobs\GenerateContentSprint;
use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('it generates content sprint ideas', function () {
    $sprint = ContentSprint::factory()->forBrand($this->brand)->create([
        'status' => ContentSprintStatus::Pending,
        'inputs' => [
            'topics' => ['marketing', 'social media'],
            'goals' => 'Increase engagement',
            'content_count' => 10,
        ],
    ]);

    $mockIdeas = [
        ['title' => 'Idea 1', 'description' => 'Description 1'],
        ['title' => 'Idea 2', 'description' => 'Description 2'],
    ];

    $this->mock(AIService::class, function (MockInterface $mock) use ($mockIdeas) {
        $mock->shouldReceive('generateContentSprintIdeas')
            ->once()
            ->with(
                \Mockery::type(Brand::class),
                ['marketing', 'social media'],
                'Increase engagement',
                10
            )
            ->andReturn($mockIdeas);
    });

    $job = new GenerateContentSprint($sprint);
    $job->handle(app(AIService::class));

    $sprint->refresh();

    expect($sprint->status)->toBe(ContentSprintStatus::Completed)
        ->and($sprint->generated_content)->toBe($mockIdeas)
        ->and($sprint->completed_at)->not->toBeNull();
});

test('it sets status to generating while processing', function () {
    $sprint = ContentSprint::factory()->forBrand($this->brand)->create([
        'status' => ContentSprintStatus::Pending,
    ]);

    $statusDuringGeneration = null;

    $this->mock(AIService::class, function (MockInterface $mock) use ($sprint, &$statusDuringGeneration) {
        $mock->shouldReceive('generateContentSprintIdeas')
            ->once()
            ->andReturnUsing(function () use ($sprint, &$statusDuringGeneration) {
                $sprint->refresh();
                $statusDuringGeneration = $sprint->status;

                return [];
            });
    });

    $job = new GenerateContentSprint($sprint);
    $job->handle(app(AIService::class));

    expect($statusDuringGeneration)->toBe(ContentSprintStatus::Generating);
});

test('it sets status to failed on exception', function () {
    $sprint = ContentSprint::factory()->forBrand($this->brand)->create([
        'status' => ContentSprintStatus::Pending,
    ]);

    $this->mock(AIService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateContentSprintIdeas')
            ->once()
            ->andThrow(new \Exception('AI service unavailable'));
    });

    $job = new GenerateContentSprint($sprint);

    try {
        $job->handle(app(AIService::class));
    } catch (\Exception $e) {
        // Expected
    }

    $sprint->refresh();

    expect($sprint->status)->toBe(ContentSprintStatus::Failed);
});

test('it rethrows exception after setting failed status', function () {
    $sprint = ContentSprint::factory()->forBrand($this->brand)->create([
        'status' => ContentSprintStatus::Pending,
    ]);

    $this->mock(AIService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateContentSprintIdeas')
            ->once()
            ->andThrow(new \Exception('API error'));
    });

    $job = new GenerateContentSprint($sprint);

    expect(fn () => $job->handle(app(AIService::class)))->toThrow(\Exception::class, 'API error');
});

test('it handles empty topics', function () {
    $sprint = ContentSprint::factory()->forBrand($this->brand)->create([
        'status' => ContentSprintStatus::Pending,
        'inputs' => [
            'goals' => 'Some goals',
            'content_count' => 5,
        ],
    ]);

    $this->mock(AIService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateContentSprintIdeas')
            ->once()
            ->with(\Mockery::type(Brand::class), [], 'Some goals', 5)
            ->andReturn([]);
    });

    $job = new GenerateContentSprint($sprint);
    $job->handle(app(AIService::class));

    $sprint->refresh();

    expect($sprint->status)->toBe(ContentSprintStatus::Completed);
});

test('it uses default content count when not specified', function () {
    $sprint = ContentSprint::factory()->forBrand($this->brand)->create([
        'status' => ContentSprintStatus::Pending,
        'inputs' => [
            'topics' => ['topic1'],
            'goals' => 'Goal',
        ],
    ]);

    $this->mock(AIService::class, function (MockInterface $mock) {
        $mock->shouldReceive('generateContentSprintIdeas')
            ->once()
            ->with(\Mockery::type(Brand::class), ['topic1'], 'Goal', 20)
            ->andReturn([]);
    });

    $job = new GenerateContentSprint($sprint);
    $job->handle(app(AIService::class));
});

test('failed method sets status to failed', function () {
    $sprint = ContentSprint::factory()->forBrand($this->brand)->create([
        'status' => ContentSprintStatus::Generating,
    ]);

    $job = new GenerateContentSprint($sprint);
    $job->failed(new \Exception('Job failed'));

    $sprint->refresh();

    expect($sprint->status)->toBe(ContentSprintStatus::Failed);
});

test('it has correct timeout and retry configuration', function () {
    $sprint = ContentSprint::factory()->forBrand($this->brand)->create();

    $job = new GenerateContentSprint($sprint);

    expect($job->timeout)->toBe(300)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([30, 120]);
});
