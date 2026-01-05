<?php

namespace App\Policies;

use App\Models\MediaFolder;
use App\Models\User;

class MediaFolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function view(User $user, MediaFolder $folder): bool
    {
        $brand = $user->currentBrand();

        return $brand && $folder->brand_id === $brand->id;
    }

    public function create(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function update(User $user, MediaFolder $folder): bool
    {
        $brand = $user->currentBrand();

        return $brand && $folder->brand_id === $brand->id;
    }

    public function delete(User $user, MediaFolder $folder): bool
    {
        $brand = $user->currentBrand();

        return $brand && $folder->brand_id === $brand->id;
    }
}
