<?php

namespace App\Jobs;

use App\Models\Dispute;
use App\Notifications\DisputeCreatedNotification;
use App\Services\DisputeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessDisputeCreated implements ShouldQueue
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
            Log::warning('ProcessDisputeCreated: Empty dispute data in payload');

            return;
        }

        $dispute = $disputeService->createOrUpdateFromStripe($stripeDispute);

        if (! $dispute) {
            Log::warning('ProcessDisputeCreated: Could not create dispute', [
                'stripe_dispute_id' => $stripeDispute['id'] ?? 'unknown',
            ]);

            return;
        }

        $dispute->recordEvent('dispute.created', $stripeDispute);

        $this->notifyPlatformAdmins($dispute);

        GatherDisputeEvidence::dispatch($dispute)->delay(now()->addSeconds(30));

        Log::info('Dispute created and processed', [
            'dispute_id' => $dispute->id,
            'stripe_dispute_id' => $dispute->stripe_dispute_id,
            'amount' => $dispute->amount,
            'reason' => $dispute->reason->value,
        ]);
    }

    protected function notifyPlatformAdmins(Dispute $dispute): void
    {
        $adminEmails = config('admin.emails', []);

        if (empty($adminEmails)) {
            Log::warning('No platform admin emails configured for dispute notification', [
                'dispute_id' => $dispute->id,
            ]);

            return;
        }

        foreach ($adminEmails as $email) {
            Notification::route('mail', $email)
                ->notify(new DisputeCreatedNotification($dispute));
        }

        Log::info('Dispute created notification sent to platform admins', [
            'dispute_id' => $dispute->id,
            'admin_count' => count($adminEmails),
        ]);
    }
}
