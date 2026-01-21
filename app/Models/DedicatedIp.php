<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DedicatedIp extends Model
{
    /** @use HasFactory<\Database\Factories\DedicatedIpFactory> */
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'status',
        'brand_id',
        'purchased_at',
        'assigned_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Scope to get available IPs.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to get assigned IPs.
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Check if this IP is available for assignment.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if this IP is currently assigned.
     */
    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }
}
