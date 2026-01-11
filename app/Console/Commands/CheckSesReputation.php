<?php

namespace App\Console\Commands;

use Aws\Ses\SesClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSesReputation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ses:check-reputation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check SES sending reputation and alert if issues detected';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $ses = new SesClient([
            'version' => 'latest',
            'region' => config('services.ses.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ]);

        try {
            // Get send statistics for the last 24 hours
            $stats = $ses->getSendStatistics();

            // Calculate rates from last 24 hours
            $dataPoints = collect($stats['SendDataPoints'])
                ->filter(function (array $point): bool {
                    return Carbon::parse($point['Timestamp'])->isAfter(now()->subDay());
                });

            $totalDeliveries = $dataPoints->sum('DeliveryAttempts');
            $totalBounces = $dataPoints->sum('Bounces');
            $totalComplaints = $dataPoints->sum('Complaints');
            $totalRejects = $dataPoints->sum('Rejects');

            $bounceRate = $totalDeliveries > 0 ? ($totalBounces / $totalDeliveries) * 100 : 0;
            $complaintRate = $totalDeliveries > 0 ? ($totalComplaints / $totalDeliveries) * 100 : 0;

            $this->info('SES Reputation Report (Last 24 hours)');
            $this->info('=====================================');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Delivery Attempts', $totalDeliveries],
                    ['Successful Deliveries', $totalDeliveries - $totalBounces - $totalRejects],
                    ['Bounces', $totalBounces],
                    ['Complaints', $totalComplaints],
                    ['Rejects', $totalRejects],
                    ['Bounce Rate', sprintf('%.2f%%', $bounceRate)],
                    ['Complaint Rate', sprintf('%.3f%%', $complaintRate)],
                ]
            );

            // Alert thresholds - SES may suspend accounts if these are exceeded
            $hasCriticalIssue = false;

            if ($bounceRate > 5) {
                $this->error("CRITICAL: Bounce rate ({$bounceRate}%) exceeds 5% threshold!");
                Log::critical('SES bounce rate critical', [
                    'rate' => $bounceRate,
                    'bounces' => $totalBounces,
                    'deliveries' => $totalDeliveries,
                ]);
                $hasCriticalIssue = true;
            } elseif ($bounceRate > 3) {
                $this->warn("WARNING: Bounce rate ({$bounceRate}%) is approaching 5% threshold.");
                Log::warning('SES bounce rate warning', ['rate' => $bounceRate]);
            }

            if ($complaintRate > 0.1) {
                $this->error("CRITICAL: Complaint rate ({$complaintRate}%) exceeds 0.1% threshold!");
                Log::critical('SES complaint rate critical', [
                    'rate' => $complaintRate,
                    'complaints' => $totalComplaints,
                    'deliveries' => $totalDeliveries,
                ]);
                $hasCriticalIssue = true;
            } elseif ($complaintRate > 0.05) {
                $this->warn("WARNING: Complaint rate ({$complaintRate}%) is approaching 0.1% threshold.");
                Log::warning('SES complaint rate warning', ['rate' => $complaintRate]);
            }

            if (! $hasCriticalIssue && $bounceRate <= 3 && $complaintRate <= 0.05) {
                $this->info('All reputation metrics are healthy.');
            }

            // Log for monitoring
            Log::info('SES reputation check completed', [
                'deliveries' => $totalDeliveries,
                'bounces' => $totalBounces,
                'complaints' => $totalComplaints,
                'bounce_rate' => $bounceRate,
                'complaint_rate' => $complaintRate,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to check SES reputation: '.$e->getMessage());
            Log::error('Failed to check SES reputation', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }
}
