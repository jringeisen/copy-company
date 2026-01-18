<?php

namespace Database\Factories;

use App\Models\AiPrompt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiPrompt>
 */
class AiPromptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['content_sprint', 'blog_post', 'social_post', 'newsletter']),
            'industry' => fake()->optional(0.7)->randomElement(['tech', 'marketing', 'finance', 'health', 'education']),
            'system_prompt' => fake()->paragraph(3),
            'user_prompt_template' => 'Generate content about {{topic}} for {{audience}}.',
            'version' => fake()->numberBetween(1, 10),
            'active' => true,
        ];
    }

    /**
     * Indicate that the prompt is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Set a specific type for the prompt.
     */
    public function forType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Set a specific industry for the prompt.
     */
    public function forIndustry(?string $industry): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => $industry,
        ]);
    }

    /**
     * Indicate that the prompt is global (no industry).
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => null,
        ]);
    }

    /**
     * Set a specific version.
     */
    public function version(int $version): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => $version,
        ]);
    }
}
