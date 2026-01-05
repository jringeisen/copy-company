<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class MediaService
{
    protected const DISK = 'media';

    protected const THUMBNAIL_SIZE = 300;

    protected const MAX_DIMENSION = 2000;

    /**
     * Upload an image and create a media record.
     */
    public function upload(UploadedFile $file, Brand $brand, int $userId, ?int $folderId = null): Media
    {
        $filename = $this->generateFilename($file);
        $path = $this->generatePath($brand, $filename);

        // Read image dimensions before uploading
        $image = Image::read($file);
        $width = $image->width();
        $height = $image->height();

        // Optimize if too large
        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);
            $width = $image->width();
            $height = $image->height();
        }

        // Encode the image
        $encoded = $image->encodeByMediaType($file->getMimeType());
        $size = strlen($encoded->toString());

        return DB::transaction(function () use ($path, $encoded, $brand, $userId, $folderId, $file, $size, $width, $height) {
            // Store the optimized image
            Storage::disk(self::DISK)->put($path, $encoded->toString());

            // Create the media record
            $media = Media::create([
                'brand_id' => $brand->id,
                'user_id' => $userId,
                'folder_id' => $folderId,
                'filename' => $file->getClientOriginalName(),
                'disk' => self::DISK,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $size,
                'width' => $width,
                'height' => $height,
            ]);

            // Generate thumbnail
            $this->generateThumbnail($media, $file);

            return $media->fresh();
        });
    }

    /**
     * Generate a thumbnail for the media.
     */
    public function generateThumbnail(Media $media, ?UploadedFile $file = null): void
    {
        // Read from storage if no file provided
        if ($file) {
            $image = Image::read($file);
        } else {
            $contents = Storage::disk($media->disk)->get($media->path);
            $image = Image::read($contents);
        }

        // Create cover thumbnail (crops to fill the dimensions)
        $image->cover(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);

        // Generate thumbnail path
        $thumbnailPath = $this->getThumbnailPath($media->path);

        // Store thumbnail
        $encoded = $image->encodeByMediaType($media->mime_type);
        Storage::disk($media->disk)->put($thumbnailPath, $encoded->toString());

        // Update media record
        $media->update(['thumbnail_path' => $thumbnailPath]);
    }

    /**
     * Delete a media file and its thumbnail.
     */
    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);

        // Delete main file if it exists
        if ($disk->exists($media->path)) {
            $disk->delete($media->path);
        }

        // Delete thumbnail if it exists
        if ($media->thumbnail_path && $disk->exists($media->thumbnail_path)) {
            $disk->delete($media->thumbnail_path);
        }

        // Delete the record
        $media->delete();
    }

    /**
     * Move media to a different folder.
     */
    public function move(Media $media, ?int $folderId): Media
    {
        $media->update(['folder_id' => $folderId]);

        return $media;
    }

    /**
     * Update alt text for a media item.
     */
    public function updateAltText(Media $media, ?string $altText): Media
    {
        $media->update(['alt_text' => $altText]);

        return $media;
    }

    /**
     * Bulk delete media items.
     *
     * @param  array<int>  $mediaIds
     */
    public function bulkDelete(array $mediaIds, Brand $brand): int
    {
        return DB::transaction(function () use ($mediaIds, $brand) {
            $media = Media::where('brand_id', $brand->id)
                ->whereIn('id', $mediaIds)
                ->get();

            foreach ($media as $item) {
                $this->delete($item);
            }

            return $media->count();
        });
    }

    /**
     * Generate a unique filename for the uploaded file.
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = Str::uuid();

        return "{$name}.{$extension}";
    }

    /**
     * Generate the storage path for a file.
     */
    protected function generatePath(Brand $brand, string $filename): string
    {
        $date = now()->format('Y/m');

        return "brands/{$brand->id}/media/{$date}/{$filename}";
    }

    /**
     * Get the thumbnail path from the original path.
     */
    protected function getThumbnailPath(string $path): string
    {
        $pathInfo = pathinfo($path);
        $dir = $pathInfo['dirname'];
        $name = $pathInfo['filename'];
        $ext = $pathInfo['extension'];

        return "{$dir}/thumbs/{$name}.{$ext}";
    }
}
