<?php

use App\Models\Brand;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
    $this->media = Media::factory()->forBrand($this->brand)->create([
        'user_id' => $this->user->id,
        'disk' => 'public',
    ]);
});

test('media belongs to a brand', function () {
    expect($this->media->brand)->toBeInstanceOf(Brand::class)
        ->and($this->media->brand->id)->toBe($this->brand->id);
});

test('media belongs to a user', function () {
    expect($this->media->user)->toBeInstanceOf(User::class)
        ->and($this->media->user->id)->toBe($this->user->id);
});

test('media belongs to a folder', function () {
    $folder = MediaFolder::factory()->forBrand($this->brand)->create();
    $mediaWithFolder = Media::factory()->forBrand($this->brand)->create([
        'folder_id' => $folder->id,
    ]);

    expect($mediaWithFolder->folder)->toBeInstanceOf(MediaFolder::class)
        ->and($mediaWithFolder->folder->id)->toBe($folder->id);
});

test('media without folder returns null for folder relationship', function () {
    expect($this->media->folder)->toBeNull();
});

test('it generates url attribute from path', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'disk' => 'public',
        'path' => 'media/test-image.jpg',
    ]);

    expect($media->url)->toContain('test-image.jpg');
});

test('it returns null for thumbnail url when no thumbnail exists', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'thumbnail_path' => null,
    ]);

    expect($media->thumbnail_url)->toBeNull();
});

test('it generates thumbnail url when thumbnail exists', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'disk' => 'public',
        'thumbnail_path' => 'media/thumbnails/test-thumb.jpg',
    ]);

    expect($media->thumbnail_url)->toContain('test-thumb.jpg');
});

test('it formats size in megabytes', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'size' => 2097152, // 2 MB
    ]);

    expect($media->human_size)->toBe('2.00 MB');
});

test('it formats size in kilobytes', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'size' => 2048, // 2 KB
    ]);

    expect($media->human_size)->toBe('2.00 KB');
});

test('it formats size in bytes', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'size' => 512,
    ]);

    expect($media->human_size)->toBe('512 bytes');
});

test('it identifies image mime types', function () {
    $imageMedia = Media::factory()->forBrand($this->brand)->create([
        'mime_type' => 'image/jpeg',
    ]);

    $pdfMedia = Media::factory()->forBrand($this->brand)->create([
        'mime_type' => 'application/pdf',
    ]);

    expect($imageMedia->isImage())->toBeTrue()
        ->and($pdfMedia->isImage())->toBeFalse();
});

test('it returns dimensions string when width and height are set', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'width' => 1920,
        'height' => 1080,
    ]);

    expect($media->dimensions)->toBe('1920 x 1080');
});

test('it returns null for dimensions when width or height is missing', function () {
    $mediaNoWidth = Media::factory()->forBrand($this->brand)->create([
        'width' => null,
        'height' => 1080,
    ]);

    $mediaNoHeight = Media::factory()->forBrand($this->brand)->create([
        'width' => 1920,
        'height' => null,
    ]);

    $mediaNoDimensions = Media::factory()->forBrand($this->brand)->create([
        'width' => null,
        'height' => null,
    ]);

    expect($mediaNoWidth->dimensions)->toBeNull()
        ->and($mediaNoHeight->dimensions)->toBeNull()
        ->and($mediaNoDimensions->dimensions)->toBeNull();
});

test('it casts size to integer', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'size' => '12345',
    ]);

    expect($media->size)->toBeInt();
});

test('it casts width to integer', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'width' => '1920',
    ]);

    expect($media->width)->toBeInt();
});

test('it casts height to integer', function () {
    $media = Media::factory()->forBrand($this->brand)->create([
        'height' => '1080',
    ]);

    expect($media->height)->toBeInt();
});

test('it uses temporaryUrl for s3 disks when available', function () {
    // Create a mock disk that supports temporaryUrl
    $mockDisk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $mockDisk->shouldReceive('temporaryUrl')
        ->once()
        ->with('media/test.jpg', Mockery::type(\DateTime::class))
        ->andReturn('https://s3.example.com/temp-signed-url');

    Storage::shouldReceive('disk')
        ->with('media')
        ->andReturn($mockDisk);

    $media = new Media([
        'disk' => 'media',
        'path' => 'media/test.jpg',
    ]);

    $url = $media->url;

    expect($url)->toBe('https://s3.example.com/temp-signed-url');
});

test('it falls back to regular url when temporaryUrl throws RuntimeException', function () {
    // Create a mock disk that throws RuntimeException on temporaryUrl
    $mockDisk = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
    $mockDisk->shouldReceive('temporaryUrl')
        ->once()
        ->andThrow(new \RuntimeException('Temporary URLs not supported'));
    $mockDisk->shouldReceive('url')
        ->once()
        ->with('media/test.jpg')
        ->andReturn('https://s3.example.com/regular-url');

    Storage::shouldReceive('disk')
        ->with('media')
        ->andReturn($mockDisk);

    $media = new Media([
        'disk' => 'media',
        'path' => 'media/test.jpg',
    ]);

    $url = $media->url;

    expect($url)->toBe('https://s3.example.com/regular-url');
});
