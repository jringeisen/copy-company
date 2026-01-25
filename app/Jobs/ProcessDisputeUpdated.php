<?php

namespace App\Jobs;

use App\Services\DisputeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDisputeUpdated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload
    ) {}

    public function handle(DisputeService $disputeService): void
    {
        $stripeDispute = $this->payload['data']['object'] ?? [];

        if (empty($stripeDispute)) {
            Log::warning('ProcessDisputeUpdated: Empty dispute data in payload');

            return;
        }

        $stripeDisputeId = $stripeDispute['id'] ?? null;
        if (! $stripeDisputeId) {
            return;
        }

        $dispute = $disputeService->findByStripeId($stripeDisputeId);

        if (! $dispute) {
            $dispute = $disputeService->createOrUpdateFromStripe($stripeDispute);
        } else {
            $dispute = $disputeService->updateStatus($dispute, $stripeDispute);
        }

        if ($dispute) {
            $dispute->recordEvent('dispute.updated', $stripeDispute);

            Log::info('Dispute updated', [
                'dispute_id' => $dispute->id,
                'stripe_dispute_id' => $dispute->stripe_dispute_id,
                'status' => $dispute->status->value,
            ]);
        }
    }
}
