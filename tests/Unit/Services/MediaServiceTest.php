<?php

use App\Models\Brand;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('public');
});

// ===========================================
// Upload Tests
// ===========================================

test('upload creates media record with correct attributes', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $mediaService = app(MediaService::class);
    $media = $mediaService->upload($file, $brand, $user->id);

    expect($media)->toBeInstanceOf(Media::class);
    expect($media->brand_id)->toBe($brand->id);
    expect($media->user_id)->toBe($user->id);
    expect($media->filename)->toBe('test-image.jpg');
    expect($media->mime_type)->toBe('image/jpeg');
    expect($media->width)->toBe(800);
    expect($media->height)->toBe(600);
    expect($media->folder_id)->toBeNull();
});

test('upload stores file to s3 disk', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $mediaService = app(MediaService::class);
    $media = $mediaService->upload($file, $brand, $user->id);

    Storage::disk('media')->assertExists($media->path);
});

test('upload with folder sets folder_id', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $mediaService = app(MediaService::class);
    $media = $mediaService->upload($file, $brand, $user->id, $folder->id);

    expect($media->folder_id)->toBe($folder->id);
});

test('upload generates thumbnail', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $mediaService = app(MediaService::class);
    $media = $mediaService->upload($file, $brand, $user->id);

    expect($media->thumbnail_path)->not->toBeNull();
    Storage::disk('media')->assertExists($media->thumbnail_path);
});

test('upload optimizes large images', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $file = UploadedFile::fake()->image('large-image.jpg', 4000, 3000);

    $mediaService = app(MediaService::class);
    $media = $mediaService->upload($file, $brand, $user->id);

    // Image should be scaled down to max 2000px
    expect($media->width)->toBeLessThanOrEqual(2000);
    expect($media->height)->toBeLessThanOrEqual(2000);
});

// ===========================================
// Delete Tests
// ===========================================

test('delete removes media record from database', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $mediaService = app(MediaService::class);
    $media = $mediaService->upload($file, $brand, $user->id);

    $mediaId = $media->id;

    $mediaService->delete($media);

    $this->assertDatabaseMissing('media', ['id' => $mediaId]);
});

test('delete removes files from storage', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $mediaService = app(MediaService::class);
    $media = $mediaService->upload($file, $brand, $user->id);

    $path = $media->path;
    $thumbnailPath = $media->thumbnail_path;

    $mediaService->delete($media);

    Storage::disk('media')->assertMissing($path);
    Storage::disk('media')->assertMissing($thumbnailPath);
});

// ===========================================
// Move Tests
// ===========================================

test('move updates folder_id', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();
    $media = Media::factory()->forBrand($brand)->create(['folder_id' => null]);

    $mediaService = app(MediaService::class);
    $updatedMedia = $mediaService->move($media, $folder->id);

    expect($updatedMedia->folder_id)->toBe($folder->id);
});

test('move to null removes from folder', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $folder = MediaFolder::factory()->forBrand($brand)->create();
    $media = Media::factory()->forBrand($brand)->inFolder($folder)->create();

    $mediaService = app(MediaService::class);
    $updatedMedia = $mediaService->move($media, null);

    expect($updatedMedia->folder_id)->toBeNull();
});

// ===========================================
// Alt Text Tests
// ===========================================

test('updateAltText sets alt text', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->create(['alt_text' => null]);

    $mediaService = app(MediaService::class);
    $updatedMedia = $mediaService->updateAltText($media, 'A beautiful sunset');

    expect($updatedMedia->alt_text)->toBe('A beautiful sunset');
});

test('updateAltText can clear alt text', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->withAltText('Existing alt text')->create();

    $mediaService = app(MediaService::class);
    $updatedMedia = $mediaService->updateAltText($media, null);

    expect($updatedMedia->alt_text)->toBeNull();
});

// ===========================================
// Bulk Delete Tests
// ===========================================

test('bulkDelete removes multiple media items', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media1 = Media::factory()->forBrand($brand)->create();
    $media2 = Media::factory()->forBrand($brand)->create();
    $media3 = Media::factory()->forBrand($brand)->create();

    $mediaService = app(MediaService::class);
    $count = $mediaService->bulkDelete([$media1->id, $media2->id], $brand);

    expect($count)->toBe(2);
    $this->assertDatabaseMissing('media', ['id' => $media1->id]);
    $this->assertDatabaseMissing('media', ['id' => $media2->id]);
    $this->assertDatabaseHas('media', ['id' => $media3->id]);
});

test('bulkDelete only deletes media belonging to brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $media = Media::factory()->forBrand($brand)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherMedia = Media::factory()->forBrand($otherBrand)->create();

    $mediaService = app(MediaService::class);
    // Try to delete both, but only own brand's media should be deleted
    $count = $mediaService->bulkDelete([$media->id, $otherMedia->id], $brand);

    expect($count)->toBe(1);
    $this->assertDatabaseMissing('media', ['id' => $media->id]);
    $this->assertDatabaseHas('media', ['id' => $otherMedia->id]);
});

test('bulkDelete returns zero for empty array', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $mediaService = app(MediaService::class);
    $count = $mediaService->bulkDelete([], $brand);

    expect($count)->toBe(0);
});

// ===========================================
// Generate Thumbnail Tests
// ===========================================

test('generateThumbnail can regenerate from storage', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

    $mediaService = app(MediaService::class);
    $media = $mediaService->upload($file, $brand, $user->id);

    // Delete the old thumbnail
    $oldThumbnailPath = $media->thumbnail_path;
    Storage::disk('media')->delete($oldThumbnailPath);

    // Regenerate thumbnail from storage (no file argument)
    $mediaService->generateThumbnail($media);

    // Thumbnail should be regenerated
    $media->refresh();
    expect($media->thumbnail_path)->not->toBeNull();
    Storage::disk('media')->assertExists($media->thumbnail_path);
});
