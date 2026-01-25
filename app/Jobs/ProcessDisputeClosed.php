<?php

namespace App\Jobs;

use App\Models\Dispute;
use App\Notifications\DisputeResolvedNotification;
use App\Services\DisputeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessDisputeClosed implements ShouldQueue
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
            Log::warning('ProcessDisputeClosed: Empty dispute data in payload');

            return;
        }

        $stripeDisputeId = $stripeDispute['id'] ?? null;
        if (! $stripeDisputeId) {
            return;
        }

        $dispute = $disputeService->findByStripeId($stripeDisputeId);

        if (! $dispute) {
            $dispute = $disputeService->createOrUpdateFromStripe($stripeDispute);
        }

        if ($dispute) {
            $dispute = $disputeService->markClosed($dispute, $stripeDispute);
            $dispute->recordEvent('dispute.closed', $stripeDispute);

            $this->notifyPlatformAdmins($dispute);

            Log::info('Dispute closed', [
                'dispute_id' => $dispute->id,
                'stripe_dispute_id' => $dispute->stripe_dispute_id,
                'resolution' => $dispute->resolution,
            ]);
        }
    }

    protected function notifyPlatformAdmins(Dispute $dispute): void
    {
        $adminEmails = config('admin.emails', []);

        if (empty($adminEmails)) {
            Log::warning('No platform admin emails configured for dispute resolution notification', [
                'dispute_id' => $dispute->id,
            ]);

            return;
        }

        foreach ($adminEmails as $email) {
            Notification::route('mail', $email)
                ->notify(new DisputeResolvedNotification($dispute));
        }

        Log::info('Dispute resolved notification sent to platform admins', [
            'dispute_id' => $dispute->id,
            'admin_count' => count($adminEmails),
        ]);
    }
}
