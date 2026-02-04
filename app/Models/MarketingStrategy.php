<?php

namespace App\Models;

use App\Enums\MarketingStrategyStatus;
use App\Models\Concerns\HasStatusScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingStrategy extends Model
{
    use HasFactory, HasStatusScopes;

    protected $fillable = [
        'brand_id',
        'week_start',
        'week_end',
        'status',
        'strategy_content',
        'context_snapshot',
        'converted_items',
        'completed_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'status' => MarketingStrategyStatus::class,
        'strategy_content' => 'array',
        'context_snapshot' => 'array',
        'converted_items' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @param  Builder<MarketingStrategy>  $query
     * @return Builder<MarketingStrategy>
     */
    public function scopePending(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, MarketingStrategyStatus::Pending);
    }

    /**
     * @param  Builder<MarketingStrategy>  $query
     * @return Builder<MarketingStrategy>
     */
    public function scopeGenerating(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, MarketingStrategyStatus::Generating);
    }

    /**
     * @param  Builder<MarketingStrategy>  $query
     * @return Builder<MarketingStrategy>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, MarketingStrategyStatus::Completed);
    }

    /**
     * @param  Builder<MarketingStrategy>  $query
     * @return Builder<MarketingStrategy>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, MarketingStrategyStatus::Failed);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            MarketingStrategyStatus::Pending => 'gray',
            MarketingStrategyStatus::Generating => 'yellow',
            MarketingStrategyStatus::Completed => 'green',
            MarketingStrategyStatus::Failed => 'red',
        };
    }

    /**
     * Check if a specific item type and index has been converted.
     */
    public function isItemConverted(string $type, int|bool $index): bool
    {
        $converted = $this->converted_items ?? [];

        if ($type === 'newsletter') {
            return $converted['newsletter'] ?? false;
        }

        return in_array($index, $converted[$type] ?? [], true);
    }

    /**
     * Get the blog post ideas count.
     */
    public function getBlogPostsCountAttribute(): int
    {
        return count($this->strategy_content['blog_posts'] ?? []);
    }

    /**
     * Get the social posts count.
     */
    public function getSocialPostsCountAttribute(): int
    {
        return count($this->strategy_content['social_posts'] ?? []);
    }

    /**
     * Get the loops count.
     */
    public function getLoopsCountAttribute(): int
    {
        return count($this->strategy_content['loops'] ?? []);
    }
}
