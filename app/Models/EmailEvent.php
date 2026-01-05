<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    protected $fillable = [
        'subscriber_id',
        'newsletter_send_id',
        'ses_message_id',
        'event_type',
        'event_data',
        'link_url',
        'event_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'event_at' => 'datetime',
    ];

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function newsletterSend(): BelongsTo
    {
        return $this->belongsTo(NewsletterSend::class);
    }
}
