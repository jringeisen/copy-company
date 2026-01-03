<?php

namespace App\Console\Commands;

use App\Jobs\PublishScheduledPosts;
use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Publish all posts that are scheduled for now or earlier';

    public function handle(): int
    {
        $this->info('Checking for scheduled posts to publish...');

        PublishScheduledPosts::dispatch();

        $this->info('Scheduled posts job dispatched.');

        return Command::SUCCESS;
    }
}
