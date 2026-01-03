<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'content_html',
        'featured_image',
        'status',
        'published_at',
        'scheduled_at',
        'publish_to_blog',
        'send_as_newsletter',
        'generate_social',
        'seo_title',
        'seo_description',
        'tags',
        'ai_assistance_percentage',
        'view_count',
        'email_open_count',
        'email_click_count',
    ];

    protected $casts = [
        'content' => 'array',
        'tags' => 'array',
        'status' => PostStatus::class,
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'publish_to_blog' => 'boolean',
        'send_as_newsletter' => 'boolean',
        'generate_social' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug($post->title, $post->brand_id);
            }
        });
    }

    protected static function generateUniqueSlug(string $title, int $brandId): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('brand_id', $brandId)->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function newsletterSend(): HasOne
    {
        return $this->hasOne(NewsletterSend::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', PostStatus::Published);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', PostStatus::Draft);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', PostStatus::Scheduled);
    }

    public function getUrlAttribute(): string
    {
        return $this->brand->url.'/'.$this->slug;
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published;
    }
}
