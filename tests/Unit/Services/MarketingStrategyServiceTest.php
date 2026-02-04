<?php

use App\Models\Brand;
use App\Models\MarketingStrategy;
use App\Models\User;
use App\Services\MarketingStrategyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
    $this->service = new MarketingStrategyService;
});

test('convertBlogPost creates a draft post', function () {
    $strategy = MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create();

    $post = $this->service->convertBlogPost($strategy, $this->brand, $this->user->id, 0);

    expect($post->status->value)->toBe('draft')
        ->and($post->brand_id)->toBe($this->brand->id)
        ->and($post->user_id)->toBe($this->user->id)
        ->and($post->title)->toBe($strategy->strategy_content['blog_posts'][0]['title']);
});

test('convertSocialPost creates a draft social post', function () {
    $strategy = MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create();

    $socialPost = $this->service->convertSocialPost($strategy, $this->brand, 0);

    expect($socialPost->status->value)->toBe('draft')
        ->and($socialPost->brand_id)->toBe($this->brand->id)
        ->and($socialPost->ai_generated)->toBeTrue();
});

test('convertNewsletter creates draft post with newsletter flag', function () {
    $strategy = MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create();

    $post = $this->service->convertNewsletter($strategy, $this->brand, $this->user->id);

    expect($post->status->value)->toBe('draft')
        ->and($post->brand_id)->toBe($this->brand->id)
        ->and($post->send_as_newsletter)->toBeTrue();
});

test('convertLoopContent adds items to existing loop', function () {
    $loop = $this->brand->loops()->create([
        'name' => 'Weekly Tips',
        'description' => 'Tips loop',
        'is_active' => true,
        'platforms' => ['instagram'],
    ]);

    $strategy = MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create([
            'strategy_content' => array_merge(
                MarketingStrategy::factory()->completed()->make()->strategy_content,
                ['loop_content' => [
                    [
                        'loop_id' => $loop->id,
                        'loop_name' => 'Weekly Tips',
                        'suggested_items' => [
                            ['content' => 'Tip one', 'hashtags' => ['tips']],
                            ['content' => 'Tip two', 'hashtags' => ['tips']],
                        ],
                    ],
                ]]
            ),
        ]);

    $result = $this->service->convertLoopContent($strategy, $this->brand, 0, $loop->id);

    expect($result->id)->toBe($loop->id)
        ->and($result->items()->count())->toBe(2);

    $strategy->refresh();
    expect($strategy->converted_items['loop_content'])->toContain(0);
});

test('trackConvertedItem updates blog_posts correctly', function () {
    $strategy = MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create();

    $this->service->trackConvertedItem($strategy, 'blog_posts', 0);
    $strategy->refresh();
    expect($strategy->converted_items['blog_posts'])->toContain(0);

    $this->service->trackConvertedItem($strategy, 'blog_posts', 1);
    $strategy->refresh();
    expect($strategy->converted_items['blog_posts'])->toContain(0)->toContain(1);
});

test('trackConvertedItem updates newsletter as boolean', function () {
    $strategy = MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create();

    $this->service->trackConvertedItem($strategy, 'newsletter', true);
    $strategy->refresh();
    expect($strategy->converted_items['newsletter'])->toBeTrue();
});

test('trackConvertedItem does not duplicate indices', function () {
    $strategy = MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create();

    $this->service->trackConvertedItem($strategy, 'social_posts', 0);
    $this->service->trackConvertedItem($strategy, 'social_posts', 0);
    $strategy->refresh();

    expect($strategy->converted_items['social_posts'])->toHaveCount(1);
});

test('convertBlogPost throws for invalid index', function () {
    $strategy = MarketingStrategy::factory()
        ->forBrand($this->brand)
        ->completed()
        ->create();

    expect(fn () => $this->service->convertBlogPost($strategy, $this->brand, $this->user->id, 99))
        ->toThrow(\InvalidArgumentException::class);
});
