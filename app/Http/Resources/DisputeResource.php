<?php

namespace App\Http\Resources;

use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Dispute
 */
class DisputeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stripe_dispute_id' => $this->stripe_dispute_id,
            'stripe_charge_id' => $this->stripe_charge_id,
            'amount' => $this->amount,
            'amount_formatted' => '$'.$this->formattedAmount(),
            'currency' => strtoupper($this->currency),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'reason' => $this->reason->value,
            'reason_label' => $this->reason->label(),
            'evidence_due_at' => $this->evidence_due_at?->format('M d, Y g:i A'),
            'evidence_due_at_relative' => $this->evidenceTimeRemaining(),
            'is_evidence_overdue' => $this->isEvidenceOverdue(),
            'disputed_at' => $this->disputed_at?->format('M d, Y g:i A'),
            'resolved_at' => $this->resolved_at?->format('M d, Y g:i A'),
            'resolution' => $this->resolution,
            'funds_withdrawn' => $this->funds_withdrawn,
            'funds_reinstated' => $this->funds_reinstated,
            'evidence_submitted' => $this->evidence_submitted,
            'is_resolved' => $this->isResolved(),
            'requires_action' => $this->requiresAction(),
            'created_at' => $this->created_at?->format('M d, Y g:i A'),
            'account_name' => $this->whenLoaded('account', fn () => $this->account?->name),
        ];
    }
}
