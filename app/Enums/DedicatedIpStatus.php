<?php

namespace App\Enums;

enum DedicatedIpStatus: string
{
    case None = 'none';
    case Active = 'active';
    case Suspended = 'suspended';
    case Released = 'released';

    /**
     * Get the display label for this status.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'No Dedicated IP',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Released => 'Released',
        };
    }

    /**
     * Check if this status allows sending via dedicated IP.
     */
    public function canUseDedicatedIp(): bool
    {
        return $this === self::Active;
    }
}
