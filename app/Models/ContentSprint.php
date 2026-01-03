<?php

namespace App\Models;

use App\Enums\ContentSprintStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentSprint extends Model
{
    use HasFactory;

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

    public function scopePending($query)
    {
        return $query->where('status', ContentSprintStatus::Pending);
    }

    public function scopeGenerating($query)
    {
        return $query->where('status', ContentSprintStatus::Generating);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ContentSprintStatus::Completed);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', ContentSprintStatus::Failed);
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
            default => 'gray',
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
