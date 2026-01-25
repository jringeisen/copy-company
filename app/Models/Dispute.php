<?php

namespace App\Models;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    /** @use HasFactory<\Database\Factories\DisputeFactory> */
    use HasFactory;

    protected $fillable = [
        'account_id',
        'stripe_dispute_id',
        'stripe_charge_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
        'reason',
        'evidence_due_at',
        'disputed_at',
        'resolved_at',
        'resolution',
        'funds_withdrawn',
        'funds_reinstated',
        'evidence_submitted',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DisputeStatus::class,
            'reason' => DisputeReason::class,
            'evidence_due_at' => 'datetime',
            'disputed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'funds_withdrawn' => 'boolean',
            'funds_reinstated' => 'boolean',
            'evidence_submitted' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the account that owns the dispute.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the events for this dispute.
     *
     * @return HasMany<DisputeEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(DisputeEvent::class)->orderBy('event_at', 'desc');
    }

    /**
     * Get the amount formatted as currency.
     */
    public function formattedAmount(): string
    {
        $amount = $this->amount / 100;

        return number_format($amount, 2);
    }

    /**
     * Check if the dispute is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status->isResolved();
    }

    /**
     * Check if the dispute requires action.
     */
    public function requiresAction(): bool
    {
        return $this->status->requiresAction();
    }

    /**
     * Check if evidence submission deadline has passed.
     */
    public function isEvidenceOverdue(): bool
    {
        if (! $this->evidence_due_at) {
            return false;
        }

        return $this->evidence_due_at->isPast();
    }

    /**
     * Get the time remaining until evidence is due.
     */
    public function evidenceTimeRemaining(): ?string
    {
        if (! $this->evidence_due_at || $this->isEvidenceOverdue()) {
            return null;
        }

        return $this->evidence_due_at->diffForHumans();
    }

    /**
     * Record an event for this dispute.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordEvent(string $eventType, array $data = []): DisputeEvent
    {
        return $this->events()->create([
            'event_type' => $eventType,
            'event_data' => $data,
            'event_at' => now(),
        ]);
    }

    /**
     * Scope to get open disputes (not resolved).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Dispute>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Dispute>
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            DisputeStatus::Won->value,
            DisputeStatus::Lost->value,
            DisputeStatus::WarningClosed->value,
        ]);
    }

    /**
     * Scope to get disputes requiring action.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Dispute>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Dispute>
     */
    public function scopeRequiringAction($query)
    {
        return $query->whereIn('status', [
            DisputeStatus::NeedsResponse->value,
            DisputeStatus::WarningNeedsResponse->value,
        ]);
    }
}
