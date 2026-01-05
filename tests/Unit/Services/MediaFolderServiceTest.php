<?php

use App\Models\Brand;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\User;
use App\Services\MediaFolderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');
});

// ===========================================
// Create Tests
// ===========================================

test('create creates a folder with correct attributes', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $service = app(MediaFolderService::class);
    $folder = $service->create($brand, 'My Folder');

    expect($folder)->toBeInstanceOf(MediaFolder::class);
    expect($folder->brand_id)->toBe($brand->id);
    expect($folder->name)->toBe('My Folder');
    expect($folder->parent_id)->toBeNull();
    expect($folder->slug)->not->toBeEmpty();
});

test('create can create nested folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $parent = MediaFolder::factory()->forBrand($brand)->create();

    $service = app(MediaFolderService::class);
    $folder = $service->create($brand, 'Child Folder', $parent->id);

    expect($folder->parent_id)->toBe($parent->id);
});

test('create generates unique slug', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $service = app(MediaFolderService::class);
    $folder1 = $service->create($brand, 'Same Name');
    $folder2 = $service->create($brand, 'Same Name');

    expect($folder1->slug)->not->toBe($folder2->slug);
});

// ===========================================
// Rename Tests
// ===========================================

test('rename updates folder name', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create(['name' => 'Old Name']);

    $service = app(MediaFolderService::class);
    $updated = $service->rename($folder, 'New Name');

    expect($updated->name)->toBe('New Name');
});

test('rename updates slug', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create(['name' => 'Old Name', 'slug' => 'old-name']);

    $service = app(MediaFolderService::class);
    $updated = $service->rename($folder, 'New Name');

    expect($updated->slug)->toContain('new-name');
});

// ===========================================
// Delete Tests
// ===========================================

test('delete removes folder from database', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();

    $folderId = $folder->id;

    $service = app(MediaFolderService::class);
    $service->delete($folder);

    $this->assertDatabaseMissing('media_folders', ['id' => $folderId]);
});

test('delete removes child folders', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $parent = MediaFolder::factory()->forBrand($brand)->create();
    $child = MediaFolder::factory()->forBrand($brand)->withParent($parent)->create();

    $parentId = $parent->id;
    $childId = $child->id;

    $service = app(MediaFolderService::class);
    $service->delete($parent);

    $this->assertDatabaseMissing('media_folders', ['id' => $parentId]);
    $this->assertDatabaseMissing('media_folders', ['id' => $childId]);
});

test('delete removes media in folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();
    $media = Media::factory()->forBrand($brand)->inFolder($folder)->create();

    $folderId = $folder->id;
    $mediaId = $media->id;

    $service = app(MediaFolderService::class);
    $service->delete($folder);

    $this->assertDatabaseMissing('media_folders', ['id' => $folderId]);
    $this->assertDatabaseMissing('media', ['id' => $mediaId]);
});

// ===========================================
// Get Tree Tests
// ===========================================

test('getTree returns root folders for brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    MediaFolder::factory()->forBrand($brand)->count(3)->create();

    $service = app(MediaFolderService::class);
    $tree = $service->getTree($brand);

    expect($tree)->toHaveCount(3);
});

test('getTree only returns folders for specified brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    MediaFolder::factory()->forBrand($brand)->count(2)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    MediaFolder::factory()->forBrand($otherBrand)->count(3)->create();

    $service = app(MediaFolderService::class);
    $tree = $service->getTree($brand);

    expect($tree)->toHaveCount(2);
});

test('getTree does not include nested folders at root level', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $parent = MediaFolder::factory()->forBrand($brand)->create();
    MediaFolder::factory()->forBrand($brand)->withParent($parent)->create();

    $service = app(MediaFolderService::class);
    $tree = $service->getTree($brand);

    // Only the root parent should be returned
    expect($tree)->toHaveCount(1);
});

// ===========================================
// Move Tests
// ===========================================

test('move updates parent_id', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();
    $newParent = MediaFolder::factory()->forBrand($brand)->create();

    $service = app(MediaFolderService::class);
    $moved = $service->move($folder, $newParent->id);

    expect($moved->parent_id)->toBe($newParent->id);
});

test('move to null moves to root', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $parent = MediaFolder::factory()->forBrand($brand)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->withParent($parent)->create();

    $service = app(MediaFolderService::class);
    $moved = $service->move($folder, null);

    expect($moved->parent_id)->toBeNull();
});

test('move prevents moving folder into itself', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();

    $service = app(MediaFolderService::class);

    expect(fn () => $service->move($folder, $folder->id))
        ->toThrow(InvalidArgumentException::class);
});

test('move prevents moving folder into descendant', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $parent = MediaFolder::factory()->forBrand($brand)->create();
    $child = MediaFolder::factory()->forBrand($brand)->withParent($parent)->create();
    $grandchild = MediaFolder::factory()->forBrand($brand)->withParent($child)->create();

    $service = app(MediaFolderService::class);

    expect(fn () => $service->move($parent, $grandchild->id))
        ->toThrow(InvalidArgumentException::class);
});
