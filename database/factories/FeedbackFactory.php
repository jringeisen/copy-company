<?php

namespace Database\Factories;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'brand_id' => null,
            'type' => fake()->randomElement(FeedbackType::cases()),
            'priority' => fake()->randomElement(FeedbackPriority::cases()),
            'status' => FeedbackStatus::Open,
            'description' => fake()->paragraph(),
            'page_url' => fake()->url(),
            'user_agent' => fake()->userAgent(),
            'screenshot_path' => null,
            'admin_notes' => null,
            'resolved_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FeedbackStatus::Open,
            'resolved_at' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FeedbackStatus::Resolved,
            'resolved_at' => now(),
            'admin_notes' => fake()->paragraph(),
        ]);
    }

    public function withScreenshot(): static
    {
        return $this->state(fn (array $attributes) => [
            'screenshot_path' => fake()->uuid().'.png',
        ]);
    }
}
