<?php

use App\Models\Brand;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

test('folder belongs to a brand', function () {
    $folder = MediaFolder::factory()->forBrand($this->brand)->create();

    expect($folder->brand)->toBeInstanceOf(Brand::class)
        ->and($folder->brand->id)->toBe($this->brand->id);
});

test('folder can have a parent folder', function () {
    $parentFolder = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Parent']);
    $childFolder = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Child',
        'parent_id' => $parentFolder->id,
    ]);

    expect($childFolder->parent)->toBeInstanceOf(MediaFolder::class)
        ->and($childFolder->parent->id)->toBe($parentFolder->id);
});

test('folder without parent returns null', function () {
    $folder = MediaFolder::factory()->forBrand($this->brand)->create(['parent_id' => null]);

    expect($folder->parent)->toBeNull();
});

test('folder can have children folders', function () {
    $parentFolder = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Parent']);
    $child1 = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Child 1',
        'parent_id' => $parentFolder->id,
    ]);
    $child2 = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Child 2',
        'parent_id' => $parentFolder->id,
    ]);

    expect($parentFolder->children)->toHaveCount(2)
        ->and($parentFolder->children->pluck('id')->toArray())->toContain($child1->id)
        ->and($parentFolder->children->pluck('id')->toArray())->toContain($child2->id);
});

test('folder can have media files', function () {
    $folder = MediaFolder::factory()->forBrand($this->brand)->create();
    Media::factory()->forBrand($this->brand)->count(3)->create(['folder_id' => $folder->id]);

    expect($folder->media)->toHaveCount(3);
});

test('it auto-generates slug on creation', function () {
    $folder = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'My Awesome Folder',
        'slug' => null,
    ]);

    expect($folder->slug)->toBe('my-awesome-folder');
});

test('it generates unique slug when duplicate exists', function () {
    MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Test Folder',
        'slug' => 'test-folder',
    ]);

    $folder2 = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Test Folder',
        'slug' => null,
    ]);

    expect($folder2->slug)->toBe('test-folder-1');
});

test('it generates unique slug incrementally', function () {
    MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Test Folder',
        'slug' => 'test-folder',
    ]);
    MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Test Folder',
        'slug' => 'test-folder-1',
    ]);

    $folder3 = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Test Folder',
        'slug' => null,
    ]);

    expect($folder3->slug)->toBe('test-folder-2');
});

test('same slug can exist in different parent folders', function () {
    $parent1 = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Parent 1']);
    $parent2 = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Parent 2']);

    $child1 = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Subfolder',
        'parent_id' => $parent1->id,
        'slug' => null,
    ]);

    $child2 = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Subfolder',
        'parent_id' => $parent2->id,
        'slug' => null,
    ]);

    expect($child1->slug)->toBe('subfolder')
        ->and($child2->slug)->toBe('subfolder');
});

test('descendants relationship returns nested children', function () {
    $root = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Root']);
    $child = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Child',
        'parent_id' => $root->id,
    ]);
    $grandchild = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Grandchild',
        'parent_id' => $child->id,
    ]);

    $rootWithDescendants = MediaFolder::with('descendants')->find($root->id);

    expect($rootWithDescendants->descendants)->toHaveCount(1)
        ->and($rootWithDescendants->descendants->first()->descendants)->toHaveCount(1);
});

test('ancestors relationship returns parent chain', function () {
    $root = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Root']);
    $child = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Child',
        'parent_id' => $root->id,
    ]);
    $grandchild = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Grandchild',
        'parent_id' => $child->id,
    ]);

    $grandchildWithAncestors = MediaFolder::with('ancestors')->find($grandchild->id);

    expect($grandchildWithAncestors->ancestors)->not->toBeNull()
        ->and($grandchildWithAncestors->ancestors->name)->toBe('Child');
});

test('path attribute returns folder name for root folder', function () {
    $folder = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Root Folder']);

    expect($folder->path)->toBe('Root Folder');
});

test('path attribute returns full path with ancestors', function () {
    $root = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Root']);
    $child = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Child',
        'parent_id' => $root->id,
    ]);
    $grandchild = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Grandchild',
        'parent_id' => $child->id,
    ]);

    // Load ancestors relationship for proper path calculation
    $grandchildWithAncestors = MediaFolder::with('ancestors')->find($grandchild->id);

    expect($grandchildWithAncestors->path)->toBe('Root / Child / Grandchild');
});

test('path attribute works with parent relationship', function () {
    $root = MediaFolder::factory()->forBrand($this->brand)->create(['name' => 'Root']);
    $child = MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Child',
        'parent_id' => $root->id,
    ]);

    $childWithParent = MediaFolder::with('parent')->find($child->id);

    expect($childWithParent->path)->toBe('Root / Child');
});

test('generateUniqueSlug static method works correctly', function () {
    MediaFolder::factory()->forBrand($this->brand)->create([
        'name' => 'Test',
        'slug' => 'test',
    ]);

    $slug = MediaFolder::generateUniqueSlug('Test', $this->brand->id, null);

    expect($slug)->toBe('test-1');
});
