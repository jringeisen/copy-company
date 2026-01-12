<?php

namespace Tests\Feature\Jobs;

use App\Enums\DayOfWeek;
use App\Enums\SocialPlatform;
use App\Jobs\ProcessScheduledLoops;
use App\Jobs\PublishSocialPost;
use App\Models\Account;
use App\Models\Brand;
use App\Models\Loop;
use App\Models\LoopItem;
use App\Models\LoopSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessScheduledLoopsTest extends TestCase
{
    use RefreshDatabase;

    protected Brand $brand;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->account = Account::factory()->create();
        $this->account->users()->attach($user->id, ['role' => 'admin']);
        $this->brand = Brand::factory()->forAccount($this->account)->create(['timezone' => 'America/New_York']);
    }

    public function test_does_nothing_when_no_active_loops(): void
    {
        Queue::fake();

        Loop::factory()->forBrand($this->brand)->paused()->create();

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertNothingPushed();
    }

    public function test_does_nothing_when_loops_have_no_items(): void
    {
        Queue::fake();

        $loop = Loop::factory()->forBrand($this->brand)->create();
        LoopSchedule::factory()->forLoop($loop)->create();

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertNothingPushed();
    }

    public function test_does_nothing_when_loops_have_no_schedules(): void
    {
        Queue::fake();

        $loop = Loop::factory()->forBrand($this->brand)->create();
        LoopItem::factory()->forLoop($loop)->create();

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertNothingPushed();
    }

    public function test_publishes_when_schedule_matches_current_day_and_time(): void
    {
        Queue::fake();

        // Set current time to Monday at 9:00 AM in New York timezone
        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop)->create(['content' => 'Test content']);
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertPushed(PublishSocialPost::class);

        Carbon::setTestNow();
    }

    public function test_does_not_publish_when_day_does_not_match(): void
    {
        Queue::fake();

        // Set current time to Monday
        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop)->create();
        // Schedule for Tuesday (different day)
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => DayOfWeek::Tuesday->value,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertNothingPushed();

        Carbon::setTestNow();
    }

    public function test_does_not_publish_when_time_does_not_match(): void
    {
        Queue::fake();

        // Set current time to Monday at 9:00 AM
        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop)->create();
        // Schedule for 10:00 AM (different time)
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '10:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertNothingPushed();

        Carbon::setTestNow();
    }

    public function test_advances_loop_position_after_publishing(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
            'current_position' => 0,
        ]);
        LoopItem::factory()->forLoop($loop)->atPosition(0)->create();
        LoopItem::factory()->forLoop($loop)->atPosition(1)->create();
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        expect($loop->refresh()->current_position)->toBe(1);

        Carbon::setTestNow();
    }

    public function test_records_times_posted_on_item(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
        ]);
        $item = LoopItem::factory()->forLoop($loop)->create(['times_posted' => 0]);
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        expect($item->refresh()->times_posted)->toBe(1);
        expect($item->last_posted_at)->not->toBeNull();

        Carbon::setTestNow();
    }

    public function test_publishes_to_multiple_platforms(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook', 'facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop)->create();
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertPushed(PublishSocialPost::class, 2);

        Carbon::setTestNow();
    }

    public function test_skips_platforms_item_does_not_qualify_for(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook', 'instagram'], // Instagram requires media
            'is_active' => true,
        ]);
        // Item without media - won't qualify for Instagram
        LoopItem::factory()->forLoop($loop)->create(['media' => []]);
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        // Should only publish to Twitter (Instagram skipped due to no media)
        Queue::assertPushed(PublishSocialPost::class, 1);

        Carbon::setTestNow();
    }

    public function test_does_not_advance_position_when_no_platforms_published(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['instagram'], // Only Instagram which requires media
            'is_active' => true,
            'current_position' => 0,
        ]);
        // Item without media - won't qualify for Instagram
        LoopItem::factory()->forLoop($loop)->create(['media' => []]);
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        // Position should not advance since nothing was published
        expect($loop->refresh()->current_position)->toBe(0);

        Queue::assertNothingPushed();

        Carbon::setTestNow();
    }

    public function test_uses_schedule_specific_platform_when_set(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook', 'facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop)->create();
        // Schedule with specific platform override
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
            'platform' => SocialPlatform::Facebook->value,
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        // Should only publish to Twitter (schedule-specific platform)
        Queue::assertPushed(PublishSocialPost::class, 1);

        Carbon::setTestNow();
    }

    public function test_respects_brand_timezone(): void
    {
        Queue::fake();

        // Brand is set to America/New_York timezone
        // It's 9:00 AM in Los Angeles, which is 12:00 PM in New York
        Carbon::setTestNow(Carbon::create(2025, 1, 6, 12, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop)->create();
        // Schedule for 12:00 PM (which is the current time in New York)
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '12:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertPushed(PublishSocialPost::class);

        Carbon::setTestNow();
    }

    public function test_creates_social_post_with_correct_attributes(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop)->create([
            'content' => 'Loop post content',
            'hashtags' => ['test', 'hashtag'],
            'link' => 'https://example.com',
        ]);
        LoopSchedule::factory()->forLoop($loop)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        $this->assertDatabaseHas('social_posts', [
            'brand_id' => $this->brand->id,
            'platform' => 'facebook',
            'content' => 'Loop post content',
            'link' => 'https://example.com',
        ]);

        Carbon::setTestNow();
    }

    public function test_handles_multiple_loops_in_single_run(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::create(2025, 1, 6, 9, 0, 0, 'America/New_York'));
        $currentDayOfWeek = Carbon::now('America/New_York')->dayOfWeek;

        $loop1 = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop1)->create();
        LoopSchedule::factory()->forLoop($loop1)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $loop2 = Loop::factory()->forBrand($this->brand)->create([
            'platforms' => ['facebook'],
            'is_active' => true,
        ]);
        LoopItem::factory()->forLoop($loop2)->create();
        LoopSchedule::factory()->forLoop($loop2)->create([
            'day_of_week' => $currentDayOfWeek,
            'time_of_day' => '09:00',
        ]);

        $job = new ProcessScheduledLoops;
        $job->handle();

        Queue::assertPushed(PublishSocialPost::class, 2);

        Carbon::setTestNow();
    }
}
