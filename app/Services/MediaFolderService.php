<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\MediaFolder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MediaFolderService
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    /**
     * Create a new folder.
     */
    public function create(Brand $brand, string $name, ?int $parentId = null): MediaFolder
    {
        return MediaFolder::create([
            'brand_id' => $brand->id,
            'parent_id' => $parentId,
            'name' => $name,
        ]);
    }

    /**
     * Rename a folder.
     */
    public function rename(MediaFolder $folder, string $name): MediaFolder
    {
        // Generate new slug before updating
        $newSlug = MediaFolder::generateUniqueSlug($name, $folder->brand_id, $folder->parent_id);

        $folder->update([
            'name' => $name,
            'slug' => $newSlug,
        ]);

        return $folder->fresh();
    }

    /**
     * Delete a folder and all its contents.
     */
    public function delete(MediaFolder $folder): void
    {
        // Eager load descendants and media to avoid N+1 queries
        $folder->load(['descendants.media', 'media']);

        // Recursively collect all folder IDs including descendants
        $folderIds = $this->collectFolderIds($folder);

        DB::transaction(function () use ($folderIds) {
            // Bulk delete all media in all folders
            $allMedia = \App\Models\Media::whereIn('folder_id', $folderIds)->get();
            foreach ($allMedia as $media) {
                $this->mediaService->delete($media);
            }

            // Delete all folders in one query (children will cascade due to foreign key)
            MediaFolder::whereIn('id', $folderIds)->delete();
        });
    }

    /**
     * Collect all folder IDs from a folder and its eager-loaded descendants.
     *
     * @return array<int>
     */
    protected function collectFolderIds(MediaFolder $folder): array
    {
        $ids = [$folder->id];

        if ($folder->relationLoaded('descendants')) {
            foreach ($folder->descendants as $child) {
                $ids = array_merge($ids, $this->collectFolderIds($child));
            }
        }

        return $ids;
    }

    /**
     * Get the folder tree for a brand.
     *
     * @return Collection<int, MediaFolder>
     */
    public function getTree(Brand $brand): Collection
    {
        /** @var Collection<int, MediaFolder> */
        return $brand->mediaFolders()
            ->whereNull('parent_id')
            ->with(['descendants', 'media'])
            ->withCount('media')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a flat list of folders with their paths (ancestors eager loaded).
     *
     * @return Collection<int, MediaFolder>
     */
    public function getFlatList(Brand $brand): Collection
    {
        /** @var Collection<int, MediaFolder> */
        return $brand->mediaFolders()
            ->with('ancestors')
            ->orderBy('name')
            ->get();
    }

    /**
     * Move a folder to a new parent.
     */
    public function move(MediaFolder $folder, ?int $newParentId): MediaFolder
    {
        // Prevent moving a folder into itself or its descendants
        if ($newParentId !== null) {
            // Eager load descendants to avoid N+1 queries
            $folder->load('descendants');
            $descendantIds = $this->collectFolderIds($folder);
            if (in_array($newParentId, $descendantIds)) {
                throw new \InvalidArgumentException('Cannot move a folder into itself or its descendants.');
            }
        }

        $folder->update(['parent_id' => $newParentId]);

        return $folder->fresh();
    }
}
