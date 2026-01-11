<?php

namespace App\Enums;

enum DayOfWeek: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    public function displayName(): string
    {
        return match ($this) {
            self::Sunday => 'Sunday',
            self::Monday => 'Monday',
            self::Tuesday => 'Tuesday',
            self::Wednesday => 'Wednesday',
            self::Thursday => 'Thursday',
            self::Friday => 'Friday',
            self::Saturday => 'Saturday',
        };
    }

    /**
     * Get all days as dropdown options.
     *
     * @return array<int, array{value: int, label: string}>
     */
    public static function toDropdownOptions(): array
    {
        return array_map(fn (self $day) => [
            'value' => $day->value,
            'label' => $day->displayName(),
        ], self::cases());
    }
}
