<?php

use App\Models\Brand;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');
});

// ===========================================
// Index / List Tests
// ===========================================

test('guests cannot access media index', function () {
    $response = $this->get(route('media.index'));

    $response->assertRedirect('/login');
});

test('users with brand can view media index', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Media::factory()->forBrand($brand)->count(5)->create();

    $response = $this->actingAs($user)->get(route('media.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Media/Index')
        ->has('media')
        ->has('folders')
    );
});

test('media list api returns media for current brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Media::factory()->forBrand($brand)->count(3)->create();

    $response = $this->actingAs($user)->get(route('media.list'));

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

test('media list filters by folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();

    Media::factory()->forBrand($brand)->count(2)->create();
    Media::factory()->forBrand($brand)->inFolder($folder)->count(3)->create();

    $response = $this->actingAs($user)->get(route('media.list', ['folder_id' => $folder->id]));

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

test('media list filters by search', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    Media::factory()->forBrand($brand)->create(['filename' => 'logo.png']);
    Media::factory()->forBrand($brand)->create(['filename' => 'banner.jpg']);
    Media::factory()->forBrand($brand)->create(['filename' => 'company-logo.png']);

    $response = $this->actingAs($user)->get(route('media.list', ['search' => 'logo']));

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

// ===========================================
// Upload Tests
// ===========================================

test('users can upload images', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $response = $this->actingAs($user)->post(route('media.store'), [
        'images' => [$file],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('media', [
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'mime_type' => 'image/jpeg',
    ]);
});

test('users can upload multiple images', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $files = [
        UploadedFile::fake()->image('image1.jpg', 800, 600),
        UploadedFile::fake()->image('image2.png', 1024, 768),
        UploadedFile::fake()->image('image3.gif', 400, 400),
    ];

    $response = $this->actingAs($user)->post(route('media.store'), [
        'images' => $files,
    ]);

    $response->assertRedirect();
    expect(Media::where('brand_id', $brand->id)->count())->toBe(3);
});

test('users can upload images to a folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();

    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $response = $this->actingAs($user)->post(route('media.store'), [
        'images' => [$file],
        'folder_id' => $folder->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('media', [
        'brand_id' => $brand->id,
        'folder_id' => $folder->id,
    ]);
});

test('upload rejects files that are too large', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    // Create a file larger than 10MB
    $file = UploadedFile::fake()->create('large-image.jpg', 11 * 1024, 'image/jpeg');

    $response = $this->actingAs($user)->post(route('media.store'), [
        'images' => [$file],
    ]);

    $response->assertSessionHasErrors('images.0');
});

test('upload rejects non-image files', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->actingAs($user)->post(route('media.store'), [
        'images' => [$file],
    ]);

    $response->assertSessionHasErrors('images.0');
});

test('users cannot upload to folders from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherFolder = MediaFolder::factory()->forBrand($otherBrand)->create();

    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $response = $this->actingAs($user)->post(route('media.store'), [
        'images' => [$file],
        'folder_id' => $otherFolder->id,
    ]);

    $response->assertSessionHasErrors('folder_id');
});

// ===========================================
// Update Tests
// ===========================================

test('users can update media alt text', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->create();

    $response = $this->actingAs($user)->patch(route('media.update', $media), [
        'alt_text' => 'A beautiful sunset over the ocean',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('media', [
        'id' => $media->id,
        'alt_text' => 'A beautiful sunset over the ocean',
    ]);
});

test('users cannot update media from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherMedia = Media::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->patch(route('media.update', $otherMedia), [
        'alt_text' => 'Trying to update someone else\'s media',
    ]);

    $response->assertForbidden();
});

// ===========================================
// Delete Tests
// ===========================================

test('users can delete their media', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->create();

    $response = $this->actingAs($user)->delete(route('media.destroy', $media));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('media', ['id' => $media->id]);
});

test('users cannot delete media from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherMedia = Media::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->delete(route('media.destroy', $otherMedia));

    $response->assertForbidden();
    $this->assertDatabaseHas('media', ['id' => $otherMedia->id]);
});

// ===========================================
// Bulk Delete Tests
// ===========================================

test('users can bulk delete media', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->count(5)->create();

    $idsToDelete = $media->take(3)->pluck('id')->toArray();

    $response = $this->actingAs($user)->post(route('media.bulk-destroy'), [
        'ids' => $idsToDelete,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(Media::where('brand_id', $brand->id)->count())->toBe(2);
});

test('bulk delete validates media belongs to current brand', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherMedia = Media::factory()->forBrand($otherBrand)->count(3)->create();

    $response = $this->actingAs($user)->post(route('media.bulk-destroy'), [
        'ids' => $otherMedia->pluck('id')->toArray(),
    ]);

    $response->assertSessionHasErrors('ids.0');
    expect(Media::where('brand_id', $otherBrand->id)->count())->toBe(3);
});

// ===========================================
// Move Tests
// ===========================================

test('users can move media to a folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();
    $media = Media::factory()->forBrand($brand)->count(3)->create();

    $response = $this->actingAs($user)->post(route('media.move'), [
        'ids' => $media->pluck('id')->toArray(),
        'folder_id' => $folder->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(Media::where('folder_id', $folder->id)->count())->toBe(3);
});

test('users can move media to root (no folder)', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();
    $media = Media::factory()->forBrand($brand)->inFolder($folder)->count(3)->create();

    $response = $this->actingAs($user)->post(route('media.move'), [
        'ids' => $media->pluck('id')->toArray(),
        'folder_id' => null,
    ]);

    $response->assertRedirect();
    expect(Media::whereNull('folder_id')->where('brand_id', $brand->id)->count())->toBe(3);
});

test('users cannot move media to folders from other brands', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherFolder = MediaFolder::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->post(route('media.move'), [
        'ids' => [$media->id],
        'folder_id' => $otherFolder->id,
    ]);

    $response->assertSessionHasErrors('folder_id');
});

// ===========================================
// View / Thumbnail Proxy Tests
// ===========================================

test('public media view route redirects to storage url', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->create([
        'disk' => 'public',
        'path' => 'test/image.jpg',
    ]);

    Storage::disk('public')->put('test/image.jpg', 'fake image content');

    $response = $this->get(route('media.view', $media));

    $response->assertRedirect();
});

test('public media thumbnail route redirects to storage url', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->withThumbnail()->create([
        'disk' => 'public',
        'path' => 'test/image.jpg',
        'thumbnail_path' => 'test/image_thumb.jpg',
    ]);

    Storage::disk('public')->put('test/image_thumb.jpg', 'fake thumbnail content');

    $response = $this->get(route('media.thumbnail', $media));

    $response->assertRedirect();
});
