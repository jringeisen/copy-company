<?php

use App\Models\Brand;
use App\Models\Loop;
use App\Models\LoopItem;
use App\Models\LoopSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('loop belongs to a brand', function () {
    $loop = Loop::factory()->create();

    expect($loop->brand)->toBeInstanceOf(Brand::class);
});

test('loop has many items', function () {
    $loop = Loop::factory()->create();
    LoopItem::factory()->forLoop($loop)->count(3)->create();

    expect($loop->items)->toHaveCount(3);
    expect($loop->items->first())->toBeInstanceOf(LoopItem::class);
});

test('loop has many schedules', function () {
    $loop = Loop::factory()->create();
    LoopSchedule::factory()->forLoop($loop)->count(2)->create();

    expect($loop->schedules)->toHaveCount(2);
    expect($loop->schedules->first())->toBeInstanceOf(LoopSchedule::class);
});

test('items are ordered by position', function () {
    $loop = Loop::factory()->create();
    LoopItem::factory()->forLoop($loop)->atPosition(2)->create();
    LoopItem::factory()->forLoop($loop)->atPosition(0)->create();
    LoopItem::factory()->forLoop($loop)->atPosition(1)->create();

    $positions = $loop->items->pluck('position')->toArray();

    expect($positions)->toBe([0, 1, 2]);
});

test('getNextItem returns the item at current position', function () {
    $loop = Loop::factory()->create(['current_position' => 0]);
    $item1 = LoopItem::factory()->forLoop($loop)->atPosition(0)->create();
    $item2 = LoopItem::factory()->forLoop($loop)->atPosition(1)->create();

    expect($loop->getNextItem()->id)->toBe($item1->id);

    $loop->update(['current_position' => 1]);
    $loop->refresh();

    expect($loop->getNextItem()->id)->toBe($item2->id);
});

test('getNextItem returns null when loop has no items', function () {
    $loop = Loop::factory()->create();

    expect($loop->getNextItem())->toBeNull();
});

test('getNextItem cycles back to first item after last', function () {
    $loop = Loop::factory()->create(['current_position' => 2]);
    $item1 = LoopItem::factory()->forLoop($loop)->atPosition(0)->create();
    LoopItem::factory()->forLoop($loop)->atPosition(1)->create();
    LoopItem::factory()->forLoop($loop)->atPosition(2)->create();

    // Position 2 % 3 = 2 (third item)
    expect($loop->getNextItem()->position)->toBe(2);

    // Position 3 % 3 = 0 (back to first item)
    $loop->update(['current_position' => 3]);
    $loop->refresh();

    expect($loop->getNextItem()->id)->toBe($item1->id);
});

test('advancePosition increments current position', function () {
    $loop = Loop::factory()->create(['current_position' => 0]);
    LoopItem::factory()->forLoop($loop)->count(3)->create();

    $loop->advancePosition();

    expect($loop->current_position)->toBe(1);
});

test('advancePosition cycles back to zero after last item', function () {
    $loop = Loop::factory()->create([
        'current_position' => 2,
        'total_cycles_completed' => 0,
    ]);
    LoopItem::factory()->forLoop($loop)->count(3)->create();

    $loop->advancePosition();

    expect($loop->current_position)->toBe(0);
    expect($loop->total_cycles_completed)->toBe(1);
});

test('advancePosition updates last posted at', function () {
    $loop = Loop::factory()->create(['last_posted_at' => null]);
    LoopItem::factory()->forLoop($loop)->create();

    $loop->advancePosition();

    expect($loop->last_posted_at)->not->toBeNull();
});

test('advancePosition does nothing when no items', function () {
    $loop = Loop::factory()->create(['current_position' => 0]);

    $loop->advancePosition();

    expect($loop->current_position)->toBe(0);
});

test('items count attribute returns correct count', function () {
    $loop = Loop::factory()->create();
    LoopItem::factory()->forLoop($loop)->count(5)->create();

    expect($loop->items_count)->toBe(5);
});

test('paused factory state sets is_active to false', function () {
    $loop = Loop::factory()->paused()->create();

    expect($loop->is_active)->toBeFalse();
});

test('withMultiplePlatforms factory state sets multiple platforms', function () {
    $loop = Loop::factory()->withMultiplePlatforms()->create();

    expect($loop->platforms)->toHaveCount(2);
    expect($loop->platforms)->toContain('instagram');
    expect($loop->platforms)->toContain('facebook');
});
