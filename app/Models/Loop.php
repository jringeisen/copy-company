<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loop extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'name',
        'description',
        'is_active',
        'platforms',
        'current_position',
        'total_cycles_completed',
        'last_posted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'platforms' => 'array',
        'current_position' => 'integer',
        'total_cycles_completed' => 'integer',
        'last_posted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<LoopItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LoopItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<LoopSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(LoopSchedule::class);
    }

    /**
     * Get the next item to post.
     */
    public function getNextItem(): ?LoopItem
    {
        $itemCount = $this->items()->count();

        if ($itemCount === 0) {
            return null;
        }

        $nextPosition = $this->current_position % $itemCount;

        return $this->items()->offset($nextPosition)->first();
    }

    /**
     * Advance to the next position, cycling if necessary.
     */
    public function advancePosition(): void
    {
        $itemCount = $this->items()->count();

        if ($itemCount === 0) {
            return;
        }

        $this->current_position++;

        // Check if we've completed a cycle
        if ($this->current_position >= $itemCount) {
            $this->current_position = 0;
            $this->total_cycles_completed++;
        }

        $this->last_posted_at = now();
        $this->save();
    }

    /**
     * Get the total number of items in the loop.
     * Uses loaded relation if available; otherwise uses withCount value or queries.
     */
    public function getItemsCountAttribute(): int
    {
        // If items_count was eager loaded via withCount, use it
        if (array_key_exists('items_count', $this->attributes)) {
            return (int) $this->attributes['items_count'];
        }

        // If items relation is already loaded, count from collection
        if ($this->relationLoaded('items')) {
            return $this->items->count();
        }

        // Fallback to query
        return $this->items()->count();
    }
}
