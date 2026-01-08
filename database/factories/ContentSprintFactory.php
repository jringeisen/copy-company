<?php

namespace Database\Factories;

use App\Enums\ContentSprintStatus;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentSprint>
 */
class ContentSprintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'inputs' => [
                'topic' => fake()->words(3, true),
                'audience' => fake()->randomElement(['entrepreneurs', 'marketers', 'developers']),
                'count' => 5,
            ],
            'generated_content' => null,
            'status' => ContentSprintStatus::Pending,
        ];
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentSprintStatus::Pending,
        ]);
    }

    public function generating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentSprintStatus::Generating,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentSprintStatus::Completed,
            'completed_at' => now(),
            'generated_content' => [
                [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'key_points' => fake()->words(3),
                    'estimated_words' => fake()->numberBetween(800, 1500),
                ],
                [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'key_points' => fake()->words(3),
                    'estimated_words' => fake()->numberBetween(800, 1500),
                ],
                [
                    'title' => fake()->sentence(),
                    'description' => fake()->paragraph(),
                    'key_points' => fake()->words(3),
                    'estimated_words' => fake()->numberBetween(800, 1500),
                ],
            ],
        ]);
    }

    public function withConvertedIdeas(array $indices): static
    {
        return $this->state(fn (array $attributes) => [
            'converted_indices' => $indices,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentSprintStatus::Failed,
        ]);
    }
}
