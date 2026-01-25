<?php

namespace App\Jobs;

use App\Models\Dispute;
use App\Services\DisputeService;
use App\Services\EvidenceGatheringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GatherDisputeEvidence implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300;

    public function __construct(
        public Dispute $dispute
    ) {}

    public function handle(
        EvidenceGatheringService $evidenceService,
        DisputeService $disputeService
    ): void {
        if ($this->dispute->evidence_submitted) {
            Log::info('Evidence already submitted for dispute', [
                'dispute_id' => $this->dispute->id,
            ]);

            return;
        }

        if ($this->dispute->isResolved()) {
            Log::info('Dispute already resolved, skipping evidence gathering', [
                'dispute_id' => $this->dispute->id,
            ]);

            return;
        }

        $evidence = $evidenceService->gatherEvidence($this->dispute);

        if (empty($evidence)) {
            Log::warning('No evidence gathered for dispute', [
                'dispute_id' => $this->dispute->id,
            ]);

            return;
        }

        $this->dispute->recordEvent('evidence.gathered', ['evidence_fields' => array_keys($evidence)]);

        $submitted = $evidenceService->submitToStripe($this->dispute, $evidence);

        if ($submitted) {
            $disputeService->markEvidenceSubmitted($this->dispute);
            $this->dispute->recordEvent('evidence.submitted', ['evidence_fields' => array_keys($evidence)]);

            Log::info('Dispute evidence submitted', [
                'dispute_id' => $this->dispute->id,
                'stripe_dispute_id' => $this->dispute->stripe_dispute_id,
            ]);
        }
    }
}
