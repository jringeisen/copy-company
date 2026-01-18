<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\EmailUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailUsage>
 */
class EmailUsageFactory extends Factory
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
            'emails_sent' => fake()->numberBetween(0, 1000),
            'period_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'reported_to_stripe' => false,
            'reported_at' => null,
        ];
    }

    /**
     * Associate the email usage with a specific account.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }

    /**
     * Indicate that the usage has been reported to Stripe.
     */
    public function reported(): static
    {
        return $this->state(fn (array $attributes) => [
            'reported_to_stripe' => true,
            'reported_at' => now(),
        ]);
    }

    /**
     * Indicate that the usage has not been reported to Stripe.
     */
    public function unreported(): static
    {
        return $this->state(fn (array $attributes) => [
            'reported_to_stripe' => false,
            'reported_at' => null,
        ]);
    }

    /**
     * Set a specific period date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => $date,
        ]);
    }

    /**
     * Set today as the period date.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_date' => now()->toDateString(),
        ]);
    }

    /**
     * Set a specific number of emails sent.
     */
    public function emailsSent(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'emails_sent' => $count,
        ]);
    }
}
