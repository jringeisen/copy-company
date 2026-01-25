<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeEvent extends Model
{
    /** @use HasFactory<\Database\Factories\DisputeEventFactory> */
    use HasFactory;

    protected $fillable = [
        'dispute_id',
        'event_type',
        'event_data',
        'event_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_data' => 'array',
            'event_at' => 'datetime',
        ];
    }

    /**
     * Get the dispute that owns this event.
     */
    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }
}
