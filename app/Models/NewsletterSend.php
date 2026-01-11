<?php

namespace App\Models;

use App\Enums\NewsletterProvider;
use App\Enums\NewsletterSendStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'brand_id',
        'subject_line',
        'preview_text',
        'provider',
        'external_campaign_id',
        'status',
        'batch_id',
        'total_recipients',
        'sent_count',
        'failed_count',
        'scheduled_at',
        'sent_at',
        'recipients_count',
        'opens',
        'unique_opens',
        'clicks',
        'unique_clicks',
        'unsubscribes',
    ];

    protected $casts = [
        'provider' => NewsletterProvider::class,
        'status' => NewsletterSendStatus::class,
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->recipients_count === 0) {
            return 0;
        }

        return round(($this->unique_opens / $this->recipients_count) * 100, 2);
    }

    public function getClickRateAttribute(): float
    {
        if ($this->recipients_count === 0) {
            return 0;
        }

        return round(($this->unique_clicks / $this->recipients_count) * 100, 2);
    }
}
