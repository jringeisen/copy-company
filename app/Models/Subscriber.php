<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'email',
        'name',
        'first_name',
        'last_name',
        'status',
        'source',
        'confirmation_token',
        'unsubscribe_token',
        'confirmed_at',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'status' => SubscriberStatus::class,
        'confirmed_at' => 'datetime',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscriber $subscriber) {
            if (! $subscriber->unsubscribe_token) {
                $subscriber->unsubscribe_token = Str::random(64);
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @param  Builder<Subscriber>  $query
     * @return Builder<Subscriber>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->email;
    }

    public function unsubscribe(): void
    {
        $this->update([
            'status' => SubscriberStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ]);
    }
}
