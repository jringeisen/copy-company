<?php

namespace App\Models;

use App\Enums\DedicatedIpStatus;
use App\Enums\NewsletterProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'ses_dedicated_ip_pool',
        'dedicated_ip_address',
        'dedicated_ip_status',
        'dedicated_ip_provisioned_at',
        'dedicated_ip_warmup_started_at',
        'dedicated_ip_warmup_completed_at',
        'warmup_day',
        'warmup_daily_stats',
        'last_warmup_send_at',
        'warmup_paused',
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
        'dedicated_ip_warmup_started_at' => 'datetime',
        'dedicated_ip_warmup_completed_at' => 'datetime',
        'warmup_daily_stats' => 'array',
        'last_warmup_send_at' => 'datetime',
        'warmup_paused' => 'boolean',
    ];

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
     * Get the dedicated IP assigned to this brand.
     */
    public function dedicatedIp(): HasOne
    {
        return $this->hasOne(DedicatedIp::class);
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
        return $this->dedicated_ip_status !== null
            && $this->dedicated_ip_status !== DedicatedIpStatus::None
            && $this->dedicated_ip_status !== DedicatedIpStatus::Released;
    }

    /**
     * Check if the brand is in the warmup period.
     */
    public function isInWarmupPeriod(): bool
    {
        return $this->dedicated_ip_status === DedicatedIpStatus::Warming;
    }

    /**
     * Get the SES Configuration Set to use for sending.
     */
    public function getSesConfigurationSet(): string
    {
        if ($this->hasDedicatedIp() && $this->ses_configuration_set) {
            return $this->ses_configuration_set;
        }

        return config('services.ses.configuration_set', 'shared-pool');
    }

    /**
     * Determine if this email should use the dedicated IP during warmup.
     * Uses random percentage based on warmup day to gradually shift traffic.
     */
    public function shouldUseDedicatedIp(): bool
    {
        $status = $this->dedicated_ip_status ?? DedicatedIpStatus::None;

        if ($status === DedicatedIpStatus::Active) {
            return true;
        }

        if ($status === DedicatedIpStatus::Warming) {
            $day = $this->warmup_day ?? 1;
            $percentage = config("services.ses.warmup_percentages.{$day}", 100);

            return random_int(1, 100) <= $percentage;
        }

        return false;
    }

    /**
     * Get the warmup progress as a percentage (0-100).
     */
    public function getWarmupProgress(): int
    {
        if ($this->dedicated_ip_status === DedicatedIpStatus::Active) {
            return 100;
        }

        if ($this->dedicated_ip_status !== DedicatedIpStatus::Warming) {
            return 0;
        }

        $day = $this->warmup_day ?? 1;
        $totalDays = 20;

        return min(100, (int) round(($day / $totalDays) * 100));
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
            'is_warming' => $this->isInWarmupPeriod(),
            'warmup_day' => $this->warmup_day,
            'warmup_progress' => $this->getWarmupProgress(),
            'warmup_paused' => $this->warmup_paused,
            'ip_address' => $this->dedicated_ip_address,
        ];
    }
}
