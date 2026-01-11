<?php

namespace Database\Factories;

use App\Models\Loop;
use App\Models\LoopItem;
use App\Models\SocialPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoopItem>
 */
class LoopItemFactory extends Factory
{
    protected $model = LoopItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loop_id' => Loop::factory(),
            'social_post_id' => null,
            'position' => 0,
            'content' => fake()->paragraph(),
            'platform' => null,
            'format' => 'feed',
            'hashtags' => fake()->words(5),
            'link' => fake()->optional()->url(),
            'media' => [],
            'times_posted' => 0,
        ];
    }

    public function forLoop(Loop $loop): static
    {
        return $this->state(fn () => ['loop_id' => $loop->id]);
    }

    public function linkedToSocialPost(SocialPost $socialPost): static
    {
        return $this->state(fn () => [
            'social_post_id' => $socialPost->id,
            'content' => null,
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn () => ['position' => $position]);
    }

    public function withTimesPosted(int $count): static
    {
        return $this->state(fn () => [
            'times_posted' => $count,
            'last_posted_at' => now(),
        ]);
    }
}
