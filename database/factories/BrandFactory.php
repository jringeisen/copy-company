<?php

namespace Database\Factories;

use App\Enums\NewsletterProvider;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'account_id' => Account::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'tagline' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'industry' => fake()->randomElement(['technology', 'health', 'finance', 'education', 'retail']),
            'primary_color' => fake()->hexColor(),
            'secondary_color' => fake()->hexColor(),
            'newsletter_provider' => NewsletterProvider::BuiltIn,
            'domain_verified' => false,
        ];
    }

    public function withCustomDomain(?string $domain = null): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_domain' => $domain ?? fake()->domainName(),
            'domain_verified' => true,
        ]);
    }

    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }

    /**
     * Associate the brand with a user by creating an account for them.
     * This is a convenience method for tests during migration from user-based to account-based ownership.
     */
    public function forUser(\App\Models\User $user): static
    {
        // Get or create an account for the user
        $account = $user->accounts()->first();
        if (! $account) {
            $account = Account::factory()->create();
            $account->users()->attach($user->id, ['role' => 'admin']);
        }

        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ])->afterCreating(function (\App\Models\Brand $brand) use ($account) {
            // Set the user's current account session
            session(['current_account_id' => $account->id]);
        });
    }
}
