<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentBrand() !== null;
    }

    public function view(User $user, Media $media): bool
    {
        $brand = $user->currentBrand();

        return $brand && $media->brand_id === $brand->id;
    }

    public function create(User $user): bool
    {
        return $user->currentBrand() !== null && $user->can('media.upload');
    }

    public function update(User $user, Media $media): bool
    {
        $brand = $user->currentBrand();

        return $brand && $media->brand_id === $brand->id && $user->can('media.upload');
    }

    public function delete(User $user, Media $media): bool
    {
        $brand = $user->currentBrand();

        return $brand && $media->brand_id === $brand->id && $user->can('media.delete');
    }
}
