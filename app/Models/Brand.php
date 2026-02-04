<?php

namespace App\Models;

use App\Enums\DedicatedIpStatus;
use App\Enums\NewsletterProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $posts_count
 * @property-read int $drafts_count
 * @property-read int $confirmed_subscribers_count
 */
class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'timezone',
        'tagline',
        'description',
        'logo_path',
        'favicon_path',
        'custom_domain',
        'domain_verified',
        'custom_email_domain',
        'custom_email_from',
        'email_domain_verification_status',
        'email_domain_dns_records',
        'email_domain_verified_at',
        'primary_color',
        'secondary_color',
        'industry',
        'voice_settings',
        'newsletter_provider',
        'newsletter_credentials',
        'social_connections',
        'onboarding_status',
        'onboarding_dismissed',
        'ses_configuration_set',
        'dedicated_ip_status',
        'dedicated_ip_provisioned_at',
    ];

    protected $casts = [
        'domain_verified' => 'boolean',
        'voice_settings' => 'array',
        'newsletter_provider' => NewsletterProvider::class,
        'newsletter_credentials' => 'encrypted:array',
        'social_connections' => 'encrypted:array',
        'onboarding_status' => 'array',
        'onboarding_dismissed' => 'boolean',
        'email_domain_dns_records' => 'array',
        'email_domain_verified_at' => 'datetime',
        'dedicated_ip_status' => DedicatedIpStatus::class,
        'dedicated_ip_provisioned_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<Subscriber, $this>
     */
    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class);
    }

    public function newsletterSends(): HasMany
    {
        return $this->hasMany(NewsletterSend::class);
    }

    /**
     * @return HasMany<SocialPost, $this>
     */
    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    /**
     * @return HasMany<Loop, $this>
     */
    public function loops(): HasMany
    {
        return $this->hasMany(Loop::class);
    }

    public function contentSprints(): HasMany
    {
        return $this->hasMany(ContentSprint::class);
    }

    /**
     * @return HasMany<MarketingStrategy, $this>
     */
    public function marketingStrategies(): HasMany
    {
        return $this->hasMany(MarketingStrategy::class);
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

        return config('app.url')."/blog/{$this->slug}";
    }

    public function getActiveSubscribersCountAttribute(): int
    {
        return $this->subscribers()->confirmed()->count();
    }

    /**
     * Check if the brand has a verified email sending domain.
     */
    public function hasVerifiedEmailDomain(): bool
    {
        return $this->email_domain_verification_status === 'verified'
            && $this->custom_email_domain !== null;
    }

    /**
     * Get the email address to send newsletters from.
     */
    public function getEmailFromAddress(): string
    {
        if ($this->hasVerifiedEmailDomain()) {
            $from = $this->custom_email_from ?: 'hello';

            return "{$from}@{$this->custom_email_domain}";
        }

        return config('mail.from.address');
    }

    /**
     * Get the email sender name.
     */
    public function getEmailFromName(): string
    {
        return $this->name;
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

    /**
     * Get the dedicated IP logs for this brand.
     */
    public function dedicatedIpLogs(): HasMany
    {
        return $this->hasMany(DedicatedIpLog::class);
    }

    /**
     * Check if the brand has an active dedicated IP.
     */
    public function hasDedicatedIp(): bool
    {
        return $this->dedicated_ip_status === DedicatedIpStatus::Active;
    }

    /**
     * Get the SES Configuration Set to use for sending.
     */
    public function getSesConfigurationSet(): string
    {
        return $this->ses_configuration_set ?? config('services.ses.configuration_set', 'shared-pool');
    }

    /**
     * Get the dedicated IP status information for display.
     *
     * @return array<string, mixed>
     */
    public function getDedicatedIpInfo(): array
    {
        $status = $this->dedicated_ip_status ?? DedicatedIpStatus::None;

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'has_dedicated_ip' => $this->hasDedicatedIp(),
        ];
    }
}
