<?php

use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('content sprint belongs to a brand', function () {
    $sprint = ContentSprint::factory()->create();

    expect($sprint->brand)->toBeInstanceOf(Brand::class);
});

test('content sprint belongs to a user', function () {
    $sprint = ContentSprint::factory()->create();

    expect($sprint->user)->toBeInstanceOf(User::class);
});

test('pending scope filters pending sprints', function () {
    $brand = Brand::factory()->create();
    ContentSprint::factory()->forBrand($brand)->pending()->count(2)->create();
    ContentSprint::factory()->forBrand($brand)->completed()->count(1)->create();

    expect(ContentSprint::pending()->count())->toBe(2);
});

test('generating scope filters generating sprints', function () {
    $brand = Brand::factory()->create();
    ContentSprint::factory()->forBrand($brand)->generating()->count(3)->create();
    ContentSprint::factory()->forBrand($brand)->pending()->count(1)->create();

    expect(ContentSprint::generating()->count())->toBe(3);
});

test('completed scope filters completed sprints', function () {
    $brand = Brand::factory()->create();
    ContentSprint::factory()->forBrand($brand)->completed()->count(2)->create();
    ContentSprint::factory()->forBrand($brand)->pending()->count(3)->create();

    expect(ContentSprint::completed()->count())->toBe(2);
});

test('failed scope filters failed sprints', function () {
    $brand = Brand::factory()->create();
    ContentSprint::factory()->forBrand($brand)->failed()->count(1)->create();
    ContentSprint::factory()->forBrand($brand)->completed()->count(2)->create();

    expect(ContentSprint::failed()->count())->toBe(1);
});

test('ideas count returns number of generated content items', function () {
    $sprint = ContentSprint::factory()->completed()->create();

    expect($sprint->ideas_count)->toBe(3);
});

test('ideas count returns zero when no content generated', function () {
    $sprint = ContentSprint::factory()->pending()->create();

    expect($sprint->ideas_count)->toBe(0);
});

test('status color returns correct color for each status', function () {
    $pending = ContentSprint::factory()->pending()->create();
    $generating = ContentSprint::factory()->generating()->create();
    $completed = ContentSprint::factory()->completed()->create();
    $failed = ContentSprint::factory()->failed()->create();

    expect($pending->status_color)->toBe('gray');
    expect($generating->status_color)->toBe('yellow');
    expect($completed->status_color)->toBe('green');
    expect($failed->status_color)->toBe('red');
});
