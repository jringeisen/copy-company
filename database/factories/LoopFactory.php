<?php

namespace Database\Factories;

use App\Enums\SocialPlatform;
use App\Models\Brand;
use App\Models\Loop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loop>
 */
class LoopFactory extends Factory
{
    protected $model = Loop::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_active' => true,
            'platforms' => [fake()->randomElement(SocialPlatform::cases())->value],
            'current_position' => 0,
            'total_cycles_completed' => 0,
        ];
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn () => ['brand_id' => $brand->id]);
    }

    public function paused(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withMultiplePlatforms(): static
    {
        return $this->state(fn () => [
            'platforms' => [
                SocialPlatform::Instagram->value,
                SocialPlatform::Facebook->value,
            ],
        ]);
    }
}
