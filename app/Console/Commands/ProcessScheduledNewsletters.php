<?php

namespace App\Console\Commands;

use App\Enums\NewsletterSendStatus;
use App\Jobs\ProcessNewsletterSend;
use App\Models\NewsletterSend;
use Illuminate\Console\Command;

class ProcessScheduledNewsletters extends Command
{
    protected $signature = 'newsletters:process-scheduled';

    protected $description = 'Process scheduled newsletters that are due to be sent';

    public function handle(): int
    {
        $dueNewsletters = NewsletterSend::query()
            ->where('status', NewsletterSendStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($dueNewsletters->isEmpty()) {
            $this->info('No scheduled newsletters due for processing.');

            return self::SUCCESS;
        }

        $this->info("Processing {$dueNewsletters->count()} scheduled newsletter(s)...");

        foreach ($dueNewsletters as $newsletterSend) {
            ProcessNewsletterSend::dispatch($newsletterSend);
            $this->line("  - Dispatched newsletter #{$newsletterSend->id}: {$newsletterSend->subject_line}");
        }

        $this->info('Done!');

        return self::SUCCESS;
    }
}
