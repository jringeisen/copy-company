<?php

namespace Database\Factories;

use App\Enums\NewsletterProvider;
use App\Models\User;
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
            'user_id' => User::factory(),
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

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
