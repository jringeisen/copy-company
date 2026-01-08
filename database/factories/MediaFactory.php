<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->uuid().'.jpg';

        return [
            'brand_id' => Brand::factory(),
            'user_id' => User::factory(),
            'folder_id' => null,
            'filename' => $filename,
            'disk' => 'public',
            'path' => 'media/'.fake()->uuid().'/'.$filename,
            'thumbnail_path' => null,
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10000, 5000000),
            'width' => fake()->numberBetween(100, 4000),
            'height' => fake()->numberBetween(100, 4000),
            'alt_text' => null,
        ];
    }

    /**
     * Associate the media with a specific brand.
     */
    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    /**
     * Associate the media with a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Associate the media with a specific folder.
     */
    public function inFolder(MediaFolder $folder): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => $folder->id,
        ]);
    }

    /**
     * Set a custom alt text.
     */
    public function withAltText(string $altText): static
    {
        return $this->state(fn (array $attributes) => [
            'alt_text' => $altText,
        ]);
    }

    /**
     * Create media with a thumbnail.
     */
    public function withThumbnail(): static
    {
        return $this->state(fn (array $attributes) => [
            'thumbnail_path' => str_replace('.jpg', '_thumb.jpg', $attributes['path']),
        ]);
    }
}
