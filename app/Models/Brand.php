<?php

namespace App\Models;

use App\Enums\NewsletterProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'tagline',
        'description',
        'logo_path',
        'favicon_path',
        'custom_domain',
        'domain_verified',
        'primary_color',
        'secondary_color',
        'industry',
        'voice_settings',
        'newsletter_provider',
        'newsletter_credentials',
        'social_connections',
        'onboarding_status',
        'onboarding_dismissed',
    ];

    protected $casts = [
        'domain_verified' => 'boolean',
        'voice_settings' => 'array',
        'newsletter_provider' => NewsletterProvider::class,
        'newsletter_credentials' => 'encrypted:array',
        'social_connections' => 'encrypted:array',
        'onboarding_status' => 'array',
        'onboarding_dismissed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class);
    }

    public function newsletterSends(): HasMany
    {
        return $this->hasMany(NewsletterSend::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function contentSprints(): HasMany
    {
        return $this->hasMany(ContentSprint::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function mediaFolders(): HasMany
    {
        return $this->hasMany(MediaFolder::class);
    }

    public function getUrlAttribute(): string
    {
        if ($this->custom_domain && $this->domain_verified) {
            return "https://{$this->custom_domain}";
        }

        return config('app.url')."/@{$this->slug}";
    }

    public function getActiveSubscribersCountAttribute(): int
    {
        return $this->subscribers()->confirmed()->count();
    }

    /**
     * Get the onboarding progress data.
     *
     * @return array<string, mixed>
     */
    public function getOnboardingProgress(): array
    {
        $status = $this->onboarding_status ?? [];

        // Compute current state from existing data
        $steps = [
            'brand_created' => true,
            'voice_configured' => ! empty($this->voice_settings['tone']) && ! empty($this->voice_settings['style']),
            'social_connected' => ! empty($this->social_connections),
            'first_post_created' => $this->posts()->exists(),
            'calendar_viewed' => $status['calendar_viewed'] ?? false,
        ];

        $completedCount = count(array_filter($steps));
        $totalSteps = count($steps);

        return [
            'steps' => $steps,
            'completed' => $completedCount,
            'total' => $totalSteps,
            'percentage' => $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0,
            'isComplete' => $completedCount === $totalSteps,
            'dismissed' => $this->onboarding_dismissed,
        ];
    }
}
