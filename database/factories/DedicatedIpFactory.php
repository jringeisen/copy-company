<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DedicatedIp>
 */
class DedicatedIpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_address' => fake()->unique()->ipv4(),
            'status' => 'available',
            'brand_id' => null,
            'purchased_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'assigned_at' => null,
            'released_at' => null,
        ];
    }

    /**
     * Indicate that the IP is assigned to a brand.
     */
    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }

    /**
     * Indicate that the IP was released.
     */
    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
            'brand_id' => null,
            'released_at' => now(),
        ]);
    }
}
