<?php

namespace App\Enums;

enum FeedbackType: string
{
    case Bug = 'bug';
    case FeatureRequest = 'feature_request';
    case Improvement = 'improvement';
    case UiUx = 'ui_ux';
    case Performance = 'performance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug Report',
            self::FeatureRequest => 'Feature Request',
            self::Improvement => 'Improvement Suggestion',
            self::UiUx => 'UI/UX Feedback',
            self::Performance => 'Performance Issue',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Bug => 'bug',
            self::FeatureRequest => 'lightbulb',
            self::Improvement => 'arrow-up',
            self::UiUx => 'paint-brush',
            self::Performance => 'bolt',
            self::Other => 'chat',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function toDropdownOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
