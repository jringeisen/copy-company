<?php

namespace Database\Factories;

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\Brand;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialPost>
 */
class SocialPostFactory extends Factory
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
            'post_id' => null,
            'platform' => fake()->randomElement(SocialPlatform::cases()),
            'format' => SocialFormat::Feed,
            'content' => fake()->paragraph(),
            'hashtags' => fake()->words(5),
            'status' => SocialPostStatus::Draft,
            'ai_generated' => true,
            'user_edited' => false,
        ];
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $post->id,
            'brand_id' => $post->brand_id,
        ]);
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    public function forPlatform(SocialPlatform $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SocialPostStatus::Draft,
        ]);
    }

    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SocialPostStatus::Queued,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SocialPostStatus::Scheduled,
            'scheduled_at' => now()->addDays(1),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SocialPostStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SocialPostStatus::Failed,
            'failure_reason' => 'API error: Rate limit exceeded',
        ]);
    }
}
