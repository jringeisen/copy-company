<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'user_id',
        'folder_id',
        'filename',
        'disk',
        'path',
        'thumbnail_path',
        'mime_type',
        'size',
        'width',
        'height',
        'alt_text',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /**
     * Get a URL for the given path, using temporaryUrl for S3 or url for local disks.
     */
    protected function getStorageUrl(string $path): string
    {
        $disk = Storage::disk($this->disk);

        // Check if disk supports temporary URLs (S3, etc.)
        if (method_exists($disk, 'temporaryUrl') && $this->disk !== 'local' && $this->disk !== 'public') {
            try {
                return $disk->temporaryUrl($path, now()->addMinutes(60));
            } catch (\RuntimeException $e) {
                // Fall back to regular URL if temporaryUrl is not supported
            }
        }

        return $disk->url($path);
    }

    /**
     * Get a temporary signed URL for the media file.
     */
    public function getUrlAttribute(): string
    {
        return $this->getStorageUrl($this->path);
    }

    /**
     * Get a temporary signed URL for the thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return $this->getStorageUrl($this->thumbnail_path);
    }

    /**
     * Get the file size in a human-readable format.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Check if the media is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get the dimensions string (e.g., "1920 x 1080").
     */
    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return "{$this->width} x {$this->height}";
        }

        return null;
    }
}
