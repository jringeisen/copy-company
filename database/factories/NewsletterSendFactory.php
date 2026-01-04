<?php

namespace Database\Factories;

use App\Enums\NewsletterProvider;
use App\Enums\NewsletterSendStatus;
use App\Models\Brand;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsletterSend>
 */
class NewsletterSendFactory extends Factory
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
            'post_id' => Post::factory(),
            'subject_line' => fake()->sentence(),
            'preview_text' => fake()->sentence(),
            'provider' => NewsletterProvider::BuiltIn,
            'status' => NewsletterSendStatus::Draft,
            'recipients_count' => fake()->numberBetween(10, 1000),
        ];
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $post->id,
            'brand_id' => $post->brand_id,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NewsletterSendStatus::Draft,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NewsletterSendStatus::Scheduled,
            'scheduled_at' => now()->addDays(1),
        ]);
    }

    public function sending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NewsletterSendStatus::Sending,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NewsletterSendStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NewsletterSendStatus::Failed,
        ]);
    }
}
