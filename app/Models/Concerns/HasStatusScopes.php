<?php

namespace App\Models\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides status scope helpers for models with status enums.
 *
 * Models using this trait should have a 'status' attribute cast to a BackedEnum.
 * The trait provides a scopeWithStatus() helper method.
 */
trait HasStatusScopes
{
    /**
     * Scope a query to filter by a specific status.
     */
    public function scopeWithStatus(Builder $query, BackedEnum $status): Builder
    {
        return $query->where('status', $status);
    }
}
