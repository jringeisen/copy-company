<?php

namespace Database\Factories;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dispute>
 */
class DisputeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'stripe_dispute_id' => 'dp_'.fake()->unique()->regexify('[A-Za-z0-9]{24}'),
            'stripe_charge_id' => 'ch_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_payment_intent_id' => 'pi_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'amount' => fake()->numberBetween(1000, 50000),
            'currency' => 'usd',
            'status' => fake()->randomElement(DisputeStatus::cases()),
            'reason' => fake()->randomElement(DisputeReason::cases()),
            'evidence_due_at' => now()->addDays(7),
            'disputed_at' => now(),
            'resolved_at' => null,
            'resolution' => null,
            'funds_withdrawn' => false,
            'funds_reinstated' => false,
            'evidence_submitted' => false,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the dispute needs response.
     */
    public function needsResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::NeedsResponse,
        ]);
    }

    /**
     * Indicate that the dispute is under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::UnderReview,
            'evidence_submitted' => true,
        ]);
    }

    /**
     * Indicate that the dispute was won.
     */
    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::Won,
            'resolution' => 'won',
            'resolved_at' => now(),
            'funds_reinstated' => true,
        ]);
    }

    /**
     * Indicate that the dispute was lost.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::Lost,
            'resolution' => 'lost',
            'resolved_at' => now(),
            'funds_withdrawn' => true,
        ]);
    }

    /**
     * Indicate that the dispute is a warning.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DisputeStatus::WarningNeedsResponse,
        ]);
    }

    /**
     * Indicate that the dispute is for fraud.
     */
    public function fraudulent(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => DisputeReason::Fraudulent,
        ]);
    }
}
