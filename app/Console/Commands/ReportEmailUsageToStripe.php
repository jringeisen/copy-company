<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\EmailUsage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class ReportEmailUsageToStripe extends Command
{
    protected $signature = 'billing:report-email-usage';

    protected $description = 'Report email usage to Stripe for metered billing';

    public function handle(): int
    {
        $meterId = config('services.stripe.email_meter_id');

        if (! $meterId) {
            $this->error('Stripe email meter ID not configured.');

            return self::FAILURE;
        }

        $stripe = new StripeClient(config('cashier.secret'));

        // Get all accounts with unreported email usage
        $accountIds = EmailUsage::query()
            ->where('reported_to_stripe', false)
            ->distinct()
            ->pluck('account_id');

        if ($accountIds->isEmpty()) {
            $this->info('No unreported email usage found.');

            return self::SUCCESS;
        }

        $this->info("Processing email usage for {$accountIds->count()} accounts...");

        $accounts = Account::query()
            ->whereIn('id', $accountIds)
            ->whereNotNull('stripe_id')
            ->get();

        $reported = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            $unreportedUsage = EmailUsage::query()
                ->where('account_id', $account->id)
                ->where('reported_to_stripe', false)
                ->get();

            $totalEmails = $unreportedUsage->sum('emails_sent');

            if ($totalEmails === 0) {
                continue;
            }

            try {
                // Report usage to Stripe Meter
                // Usage is reported in units - we bill per 1,000 emails
                $stripe->billing->meterEvents->create([
                    'event_name' => config('services.stripe.email_meter_event_name', 'email_sent'),
                    'payload' => [
                        'value' => $totalEmails,
                        'stripe_customer_id' => $account->stripe_id,
                    ],
                ]);

                // Mark all as reported
                EmailUsage::query()
                    ->whereIn('id', $unreportedUsage->pluck('id'))
                    ->update([
                        'reported_to_stripe' => true,
                        'reported_at' => now(),
                    ]);

                $reported++;

                $this->line("  Reported {$totalEmails} emails for account #{$account->id}");

                Log::info('Reported email usage to Stripe', [
                    'account_id' => $account->id,
                    'stripe_id' => $account->stripe_id,
                    'emails' => $totalEmails,
                ]);
            } catch (\Exception $e) {
                $failed++;

                $this->error("  Failed for account #{$account->id}: {$e->getMessage()}");

                Log::error('Failed to report email usage to Stripe', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Reported: {$reported}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
