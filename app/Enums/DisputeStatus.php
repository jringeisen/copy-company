<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case WarningNeedsResponse = 'warning_needs_response';
    case WarningUnderReview = 'warning_under_review';
    case WarningClosed = 'warning_closed';
    case NeedsResponse = 'needs_response';
    case UnderReview = 'under_review';
    case Won = 'won';
    case Lost = 'lost';

    /**
     * Get the display label for this status.
     */
    public function label(): string
    {
        return match ($this) {
            self::WarningNeedsResponse => 'Warning - Needs Response',
            self::WarningUnderReview => 'Warning - Under Review',
            self::WarningClosed => 'Warning - Closed',
            self::NeedsResponse => 'Needs Response',
            self::UnderReview => 'Under Review',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    /**
     * Check if the dispute is resolved (closed).
     */
    public function isResolved(): bool
    {
        return in_array($this, [self::Won, self::Lost, self::WarningClosed]);
    }

    /**
     * Check if the dispute requires action.
     */
    public function requiresAction(): bool
    {
        return in_array($this, [self::NeedsResponse, self::WarningNeedsResponse]);
    }

    /**
     * Check if this is a warning (early fraud warning).
     */
    public function isWarning(): bool
    {
        return in_array($this, [self::WarningNeedsResponse, self::WarningUnderReview, self::WarningClosed]);
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::WarningNeedsResponse, self::NeedsResponse => 'red',
            self::WarningUnderReview, self::UnderReview => 'yellow',
            self::Won, self::WarningClosed => 'green',
            self::Lost => 'gray',
        };
    }
}
