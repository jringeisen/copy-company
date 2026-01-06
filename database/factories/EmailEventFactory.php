<?php

namespace Database\Factories;

use App\Models\EmailEvent;
use App\Models\NewsletterSend;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailEvent>
 */
class EmailEventFactory extends Factory
{
    protected $model = EmailEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscriber_id' => Subscriber::factory(),
            'newsletter_send_id' => NewsletterSend::factory(),
            'ses_message_id' => fake()->uuid(),
            'event_type' => 'sent',
            'event_data' => null,
            'link_url' => null,
            'event_at' => now(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'sent',
        ]);
    }

    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'delivery',
        ]);
    }

    public function bounce(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'bounce',
        ]);
    }

    public function complaint(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'complaint',
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'open',
        ]);
    }

    public function click(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'click',
            'link_url' => fake()->url(),
        ]);
    }

    public function forSubscriber(Subscriber $subscriber): static
    {
        return $this->state(fn (array $attributes) => [
            'subscriber_id' => $subscriber->id,
        ]);
    }

    public function forNewsletterSend(NewsletterSend $newsletterSend): static
    {
        return $this->state(fn (array $attributes) => [
            'newsletter_send_id' => $newsletterSend->id,
        ]);
    }

    public function withMessageId(string $messageId): static
    {
        return $this->state(fn (array $attributes) => [
            'ses_message_id' => $messageId,
        ]);
    }
}
