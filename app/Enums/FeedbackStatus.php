<?php

namespace App\Enums;

enum FeedbackStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'blue',
            self::InProgress => 'yellow',
            self::Resolved => 'green',
            self::Closed => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::InProgress]);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Resolved, self::Closed]);
    }
}
