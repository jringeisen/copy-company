<?php

namespace Database\Factories;

use App\Enums\MarketingStrategyStatus;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketingStrategy>
 */
class MarketingStrategyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weekStart = now()->startOfWeek();

        return [
            'brand_id' => Brand::factory(),
            'week_start' => $weekStart,
            'week_end' => $weekStart->copy()->endOfWeek(),
            'status' => MarketingStrategyStatus::Pending,
            'strategy_content' => null,
            'context_snapshot' => null,
            'converted_items' => null,
        ];
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MarketingStrategyStatus::Pending,
        ]);
    }

    public function generating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MarketingStrategyStatus::Generating,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MarketingStrategyStatus::Completed,
            'completed_at' => now(),
            'strategy_content' => $this->sampleStrategyContent(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MarketingStrategyStatus::Failed,
        ]);
    }

    public function forWeek(string $weekStart): static
    {
        $start = \Carbon\Carbon::parse($weekStart)->startOfWeek();

        return $this->state(fn (array $attributes) => [
            'week_start' => $start,
            'week_end' => $start->copy()->endOfWeek(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sampleStrategyContent(): array
    {
        return [
            'week_theme' => [
                'title' => fake()->sentence(),
                'description' => fake()->paragraph(),
            ],
            'blog_posts' => [
                [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'key_points' => fake()->words(3),
                    'estimated_words' => fake()->numberBetween(800, 1500),
                    'suggested_day' => 'Tuesday',
                    'rationale' => fake()->sentence(),
                ],
                [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'key_points' => fake()->words(3),
                    'estimated_words' => fake()->numberBetween(800, 1500),
                    'suggested_day' => 'Thursday',
                    'rationale' => fake()->sentence(),
                ],
            ],
            'newsletter' => [
                'subject_line' => fake()->sentence(),
                'topic' => fake()->paragraph(),
                'key_points' => fake()->words(3),
                'suggested_day' => 'Thursday',
            ],
            'social_posts' => [
                [
                    'platform' => 'instagram',
                    'content' => fake()->paragraph(),
                    'hashtags' => fake()->words(5),
                    'suggested_day' => 'Monday',
                    'post_type' => 'educational',
                ],
                [
                    'platform' => 'facebook',
                    'content' => fake()->paragraph(),
                    'hashtags' => fake()->words(3),
                    'suggested_day' => 'Wednesday',
                    'post_type' => 'engagement',
                ],
            ],
            'loops' => [
                [
                    'name' => 'Weekly Tips Series',
                    'description' => fake()->sentence(),
                    'platforms' => ['instagram', 'facebook'],
                    'suggested_items' => [
                        [
                            'content' => fake()->paragraph(),
                            'hashtags' => fake()->words(3),
                        ],
                    ],
                    'rationale' => fake()->sentence(),
                ],
            ],
            'talking_points' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
        ];
    }
}
