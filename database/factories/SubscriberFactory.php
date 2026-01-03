<?php

namespace Database\Factories;

use App\Enums\SubscriberStatus;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscriber>
 */
class SubscriberFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'status' => SubscriberStatus::Confirmed,
            'source' => 'website',
            'confirmation_token' => Str::random(64),
            'unsubscribe_token' => Str::random(64),
            'confirmed_at' => now(),
            'subscribed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriberStatus::Pending,
            'confirmed_at' => null,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriberStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriberStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ]);
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }
}
