<?php

namespace App\Models;

use App\Enums\ContentSprintStatus;
use App\Models\Concerns\HasStatusScopes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentSprint extends Model
{
    use HasFactory, HasStatusScopes;

    protected $fillable = [
        'brand_id',
        'user_id',
        'title',
        'inputs',
        'generated_content',
        'converted_indices',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'inputs' => 'array',
        'generated_content' => 'array',
        'converted_indices' => 'array',
        'status' => ContentSprintStatus::class,
        'completed_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<ContentSprint>  $query
     * @return Builder<ContentSprint>
     */
    public function scopePending(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, ContentSprintStatus::Pending);
    }

    /**
     * @param  Builder<ContentSprint>  $query
     * @return Builder<ContentSprint>
     */
    public function scopeGenerating(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, ContentSprintStatus::Generating);
    }

    /**
     * @param  Builder<ContentSprint>  $query
     * @return Builder<ContentSprint>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, ContentSprintStatus::Completed);
    }

    /**
     * @param  Builder<ContentSprint>  $query
     * @return Builder<ContentSprint>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, ContentSprintStatus::Failed);
    }

    public function getIdeasCountAttribute(): int
    {
        return count($this->generated_content ?? []);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            ContentSprintStatus::Pending => 'gray',
            ContentSprintStatus::Generating => 'yellow',
            ContentSprintStatus::Completed => 'green',
            ContentSprintStatus::Failed => 'red',
        };
    }

    public function isIdeaConverted(int $index): bool
    {
        return in_array($index, $this->converted_indices ?? [], true);
    }

    public function getUnconvertedIdeasCountAttribute(): int
    {
        $totalIdeas = count($this->generated_content ?? []);
        $convertedCount = count($this->converted_indices ?? []);

        return $totalIdeas - $convertedCount;
    }
}
