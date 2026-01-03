<?php

namespace App\Models;

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'brand_id',
        'platform',
        'format',
        'content',
        'media',
        'hashtags',
        'link',
        'status',
        'scheduled_at',
        'published_at',
        'external_id',
        'analytics',
        'failure_reason',
        'ai_generated',
        'user_edited',
    ];

    protected $casts = [
        'platform' => SocialPlatform::class,
        'format' => SocialFormat::class,
        'status' => SocialPostStatus::class,
        'media' => 'array',
        'hashtags' => 'array',
        'analytics' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'ai_generated' => 'boolean',
        'user_edited' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', SocialPostStatus::Draft);
    }

    public function scopeQueued($query)
    {
        return $query->where('status', SocialPostStatus::Queued);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', SocialPostStatus::Scheduled);
    }

    public function scopePublished($query)
    {
        return $query->where('status', SocialPostStatus::Published);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', SocialPostStatus::Failed);
    }

    public function scopeForPlatform($query, SocialPlatform $platform)
    {
        return $query->where('platform', $platform);
    }

    // Helper methods
    public function isPublished(): bool
    {
        return $this->status === SocialPostStatus::Published;
    }

    public function canPublish(): bool
    {
        return in_array($this->status, [
            SocialPostStatus::Draft,
            SocialPostStatus::Queued,
            SocialPostStatus::Scheduled,
        ]);
    }

    public function getCharacterLimitAttribute(): int
    {
        return match ($this->platform) {
            SocialPlatform::Twitter => 280,
            SocialPlatform::Instagram => 2200,
            SocialPlatform::Facebook => 63206,
            SocialPlatform::LinkedIn => 3000,
            SocialPlatform::Pinterest => 500,
            SocialPlatform::TikTok => 2200,
            default => 2200,
        };
    }

    public function getPlatformDisplayNameAttribute(): string
    {
        return match ($this->platform) {
            SocialPlatform::Instagram => 'Instagram',
            SocialPlatform::Facebook => 'Facebook',
            SocialPlatform::Pinterest => 'Pinterest',
            SocialPlatform::LinkedIn => 'LinkedIn',
            SocialPlatform::TikTok => 'TikTok',
            SocialPlatform::Twitter => 'X (Twitter)',
            default => ucfirst($this->platform->value),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            SocialPostStatus::Draft => 'gray',
            SocialPostStatus::Queued => 'yellow',
            SocialPostStatus::Scheduled => 'blue',
            SocialPostStatus::Published => 'green',
            SocialPostStatus::Failed => 'red',
            default => 'gray',
        };
    }
}
