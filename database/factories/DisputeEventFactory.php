<?php

namespace Database\Factories;

use App\Models\Dispute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisputeEvent>
 */
class DisputeEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dispute_id' => Dispute::factory(),
            'event_type' => fake()->randomElement([
                'dispute.created',
                'dispute.updated',
                'dispute.closed',
                'dispute.funds_withdrawn',
                'dispute.funds_reinstated',
                'evidence.submitted',
            ]),
            'event_data' => [],
            'event_at' => now(),
        ];
    }
}
