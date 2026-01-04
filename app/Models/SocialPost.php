<?php

namespace App\Models;

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\Concerns\HasStatusScopes;
use App\Services\SocialPlatforms\PlatformFactory;
use App\Services\SocialPlatforms\PlatformInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPost extends Model
{
    use HasFactory, HasStatusScopes;

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

    /**
     * @param  Builder<SocialPost>  $query
     * @return Builder<SocialPost>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, SocialPostStatus::Draft);
    }

    /**
     * @param  Builder<SocialPost>  $query
     * @return Builder<SocialPost>
     */
    public function scopeQueued(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, SocialPostStatus::Queued);
    }

    /**
     * @param  Builder<SocialPost>  $query
     * @return Builder<SocialPost>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, SocialPostStatus::Scheduled);
    }

    /**
     * @param  Builder<SocialPost>  $query
     * @return Builder<SocialPost>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, SocialPostStatus::Published);
    }

    /**
     * @param  Builder<SocialPost>  $query
     * @return Builder<SocialPost>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $this->scopeWithStatus($query, SocialPostStatus::Failed);
    }

    /**
     * @param  Builder<SocialPost>  $query
     * @return Builder<SocialPost>
     */
    public function scopeForPlatform(Builder $query, SocialPlatform $platform): Builder
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

    /**
     * Get the platform strategy instance for this social post.
     */
    public function getPlatformStrategy(): PlatformInterface
    {
        return PlatformFactory::fromEnum($this->platform);
    }

    public function getCharacterLimitAttribute(): int
    {
        return $this->getPlatformStrategy()->getCharacterLimit();
    }

    public function getPlatformDisplayNameAttribute(): string
    {
        return $this->getPlatformStrategy()->getDisplayName();
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
