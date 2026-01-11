<?php

namespace App\Models;

use App\Enums\SocialFormat;
use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoopItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'loop_id',
        'social_post_id',
        'position',
        'content',
        'platform',
        'format',
        'hashtags',
        'link',
        'media',
        'times_posted',
        'last_posted_at',
    ];

    protected $casts = [
        'platform' => SocialPlatform::class,
        'format' => SocialFormat::class,
        'hashtags' => 'array',
        'media' => 'array',
        'position' => 'integer',
        'times_posted' => 'integer',
        'last_posted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Loop, $this>
     */
    public function loop(): BelongsTo
    {
        return $this->belongsTo(Loop::class);
    }

    /**
     * @return BelongsTo<SocialPost, $this>
     */
    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    /**
     * Get the content to post (from social_post or standalone).
     */
    public function getPostContent(): string
    {
        if ($this->socialPost !== null) {
            return $this->socialPost->content ?? '';
        }

        return $this->content ?? '';
    }

    /**
     * Get hashtags (from social_post or standalone).
     *
     * @return array<string>
     */
    public function getPostHashtags(): array
    {
        if ($this->socialPost !== null) {
            return $this->socialPost->hashtags ?? [];
        }

        return $this->hashtags ?? [];
    }

    /**
     * Get link (from social_post or standalone).
     */
    public function getPostLink(): ?string
    {
        if ($this->socialPost !== null) {
            return $this->socialPost->link;
        }

        return $this->link;
    }

    /**
     * Get media (from social_post or standalone).
     *
     * @return array<mixed>
     */
    public function getPostMedia(): array
    {
        if ($this->socialPost !== null) {
            return $this->socialPost->media ?? [];
        }

        return $this->media ?? [];
    }

    /**
     * Get the platform (from social_post or standalone).
     */
    public function getPostPlatform(): ?SocialPlatform
    {
        if ($this->socialPost !== null) {
            return $this->socialPost->platform;
        }

        return $this->platform;
    }

    /**
     * Get the format (from social_post or standalone).
     */
    public function getPostFormat(): SocialFormat
    {
        if ($this->socialPost !== null && $this->socialPost->format !== null) {
            return $this->socialPost->format;
        }

        return $this->format ?? SocialFormat::Feed;
    }

    /**
     * Record that this item was posted.
     */
    public function recordPosted(): void
    {
        $this->increment('times_posted');
        $this->update(['last_posted_at' => now()]);
    }

    /**
     * Check if this item is linked to a social post.
     */
    public function isLinked(): bool
    {
        return $this->social_post_id !== null;
    }
}
