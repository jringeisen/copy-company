<?php

namespace App\Models;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Feedback extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackFactory> */
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'brand_id',
        'type',
        'priority',
        'status',
        'description',
        'page_url',
        'user_agent',
        'screenshot_path',
        'admin_notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => FeedbackType::class,
            'priority' => FeedbackPriority::class,
            'status' => FeedbackStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function getScreenshotUrl(): ?string
    {
        if (! $this->screenshot_path) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl(
            'feedback/'.$this->screenshot_path,
            now()->addMinutes(60)
        );
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            FeedbackStatus::Open->value,
            FeedbackStatus::InProgress->value,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeClosed($query)
    {
        return $query->whereIn('status', [
            FeedbackStatus::Resolved->value,
            FeedbackStatus::Closed->value,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhere('page_url', 'like', "%{$search}%")
                ->orWhere('admin_notes', 'like', "%{$search}%");
        });
    }
}
