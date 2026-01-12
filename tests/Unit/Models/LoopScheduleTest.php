<?php

use App\Enums\DayOfWeek;
use App\Enums\SocialPlatform;
use App\Models\Loop;
use App\Models\LoopSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('loop schedule belongs to a loop', function () {
    $schedule = LoopSchedule::factory()->create();

    expect($schedule->loop)->toBeInstanceOf(Loop::class);
});

test('day_of_week is cast to DayOfWeek enum', function () {
    $schedule = LoopSchedule::factory()->onDay(DayOfWeek::Monday)->create();

    expect($schedule->day_of_week)->toBeInstanceOf(DayOfWeek::class);
    expect($schedule->day_of_week)->toBe(DayOfWeek::Monday);
});

test('platform is cast to SocialPlatform enum', function () {
    $schedule = LoopSchedule::factory()->create(['platform' => 'linkedin']);

    expect($schedule->platform)->toBeInstanceOf(SocialPlatform::class);
    expect($schedule->platform)->toBe(SocialPlatform::LinkedIn);
});

test('platform can be null', function () {
    $schedule = LoopSchedule::factory()->create(['platform' => null]);

    expect($schedule->platform)->toBeNull();
});

test('description attribute returns formatted day and time', function () {
    $schedule = LoopSchedule::factory()
        ->onDay(DayOfWeek::Wednesday)
        ->atTime('14:30')
        ->create();

    expect($schedule->description)->toBe('Wednesday at 2:30 PM');
});

test('description attribute handles morning times', function () {
    $schedule = LoopSchedule::factory()
        ->onDay(DayOfWeek::Friday)
        ->atTime('09:00')
        ->create();

    expect($schedule->description)->toBe('Friday at 9:00 AM');
});

test('description attribute handles noon', function () {
    $schedule = LoopSchedule::factory()
        ->onDay(DayOfWeek::Sunday)
        ->atTime('12:00')
        ->create();

    expect($schedule->description)->toBe('Sunday at 12:00 PM');
});

test('description attribute handles midnight', function () {
    $schedule = LoopSchedule::factory()
        ->onDay(DayOfWeek::Saturday)
        ->atTime('00:00')
        ->create();

    expect($schedule->description)->toBe('Saturday at 12:00 AM');
});

test('forLoop factory state sets correct loop', function () {
    $loop = Loop::factory()->create();
    $schedule = LoopSchedule::factory()->forLoop($loop)->create();

    expect($schedule->loop_id)->toBe($loop->id);
});

test('onDay factory state sets correct day', function () {
    $schedule = LoopSchedule::factory()->onDay(DayOfWeek::Tuesday)->create();

    expect($schedule->day_of_week)->toBe(DayOfWeek::Tuesday);
});

test('atTime factory state sets correct time', function () {
    $schedule = LoopSchedule::factory()->atTime('16:45')->create();

    expect($schedule->time_of_day)->toBe('16:45');
});
