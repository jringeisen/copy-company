<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'parent_id',
        'name',
        'slug',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MediaFolder $folder): void {
            if (empty($folder->slug)) {
                $folder->slug = static::generateUniqueSlug($folder->name, $folder->brand_id, $folder->parent_id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, int $brandId, ?int $parentId): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('brand_id', $brandId)
            ->where('parent_id', $parentId)
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id');
    }

    /**
     * Get all descendants (children, grandchildren, etc.) recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Recursive relationship to load all ancestors.
     * Use with eager loading: MediaFolder::with('ancestors')->find($id)
     */
    public function ancestors(): BelongsTo
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Get the full path of the folder (e.g., "Parent / Child / Grandchild").
     * For optimal performance, eager load the 'ancestors' relationship first.
     */
    public function getPathAttribute(): string
    {
        $path = collect([$this->name]);

        // Use eager-loaded ancestors if available to avoid N+1 queries
        if ($this->relationLoaded('ancestors') && $this->ancestors) {
            $ancestor = $this->ancestors;
            while ($ancestor) {
                $path->prepend($ancestor->name);
                $ancestor = $ancestor->relationLoaded('ancestors') ? $ancestor->ancestors : null;
            }
        } elseif ($this->relationLoaded('parent') && $this->parent) {
            // Fall back to parent relationship if loaded
            $parent = $this->parent;
            while ($parent) {
                $path->prepend($parent->name);
                $parent = $parent->relationLoaded('parent') ? $parent->parent : null;
            }
        } else {
            // Only query if relationships aren't loaded (legacy behavior)
            $parent = $this->parent;
            while ($parent) {
                $path->prepend($parent->name);
                $parent = $parent->parent;
            }
        }

        return $path->implode(' / ');
    }
}
