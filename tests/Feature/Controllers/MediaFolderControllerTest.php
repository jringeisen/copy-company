<?php

use App\Models\Brand;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ===========================================
// Index / List Tests
// ===========================================

test('guests cannot access folder index', function () {
    $response = $this->get(route('media.folders.index'));

    $response->assertRedirect('/login');
});

test('users with brand can view folder tree', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    MediaFolder::factory()->forBrand($brand)->count(3)->create();

    $response = $this->actingAs($user)->get(route('media.folders.index'));

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

test('folder tree only returns folders for current brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    MediaFolder::factory()->forBrand($brand)->count(2)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    MediaFolder::factory()->forBrand($otherBrand)->count(3)->create();

    $response = $this->actingAs($user)->get(route('media.folders.index'));

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

test('users without brand get empty folder list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('media.folders.index'));

    $response->assertStatus(200);
    $response->assertJsonCount(0, 'data');
});

// ===========================================
// Create Folder Tests
// ===========================================

test('users can create a folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('media.folders.store'), [
        'name' => 'My New Folder',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('media_folders', [
        'brand_id' => $brand->id,
        'name' => 'My New Folder',
        'parent_id' => null,
    ]);
});

test('users can create a nested folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $parentFolder = MediaFolder::factory()->forBrand($brand)->create();

    $response = $this->actingAs($user)->post(route('media.folders.store'), [
        'name' => 'Child Folder',
        'parent_id' => $parentFolder->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('media_folders', [
        'brand_id' => $brand->id,
        'name' => 'Child Folder',
        'parent_id' => $parentFolder->id,
    ]);
});

test('folder name is required', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $response = $this->actingAs($user)->post(route('media.folders.store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

test('users cannot create folders in another brand parent folder', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherFolder = MediaFolder::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->post(route('media.folders.store'), [
        'name' => 'Sneaky Folder',
        'parent_id' => $otherFolder->id,
    ]);

    $response->assertSessionHasErrors('parent_id');
});

// ===========================================
// Update / Rename Tests
// ===========================================

test('users can rename a folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create(['name' => 'Old Name']);

    $response = $this->actingAs($user)->patch(route('media.folders.update', $folder), [
        'name' => 'New Name',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('media_folders', [
        'id' => $folder->id,
        'name' => 'New Name',
    ]);
});

test('users cannot rename folders from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherFolder = MediaFolder::factory()->forBrand($otherBrand)->create(['name' => 'Original']);

    $response = $this->actingAs($user)->patch(route('media.folders.update', $otherFolder), [
        'name' => 'Hacked Name',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('media_folders', [
        'id' => $otherFolder->id,
        'name' => 'Original',
    ]);
});

test('rename requires a valid name', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();

    $response = $this->actingAs($user)->patch(route('media.folders.update', $folder), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

// ===========================================
// Delete Tests
// ===========================================

test('users can delete a folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();

    $response = $this->actingAs($user)->delete(route('media.folders.destroy', $folder));

    $response->assertRedirect(route('media.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('media_folders', ['id' => $folder->id]);
});

test('users cannot delete folders from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherFolder = MediaFolder::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->delete(route('media.folders.destroy', $otherFolder));

    $response->assertForbidden();
    $this->assertDatabaseHas('media_folders', ['id' => $otherFolder->id]);
});

test('deleting a parent folder cascades to children', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $parentFolder = MediaFolder::factory()->forBrand($brand)->create();
    $childFolder = MediaFolder::factory()->forBrand($brand)->withParent($parentFolder)->create();

    $response = $this->actingAs($user)->delete(route('media.folders.destroy', $parentFolder));

    $response->assertRedirect(route('media.index'));

    $this->assertDatabaseMissing('media_folders', ['id' => $parentFolder->id]);
    $this->assertDatabaseMissing('media_folders', ['id' => $childFolder->id]);
});
