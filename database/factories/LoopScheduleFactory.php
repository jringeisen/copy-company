<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Loop;
use App\Models\LoopSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoopSchedule>
 */
class LoopScheduleFactory extends Factory
{
    protected $model = LoopSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loop_id' => Loop::factory(),
            'day_of_week' => fake()->randomElement(DayOfWeek::cases())->value,
            'time_of_day' => fake()->time('H:i'),
            'platform' => null,
        ];
    }

    public function forLoop(Loop $loop): static
    {
        return $this->state(fn () => ['loop_id' => $loop->id]);
    }

    public function onDay(DayOfWeek $day): static
    {
        return $this->state(fn () => ['day_of_week' => $day->value]);
    }

    public function atTime(string $time): static
    {
        return $this->state(fn () => ['time_of_day' => $time]);
    }
}
