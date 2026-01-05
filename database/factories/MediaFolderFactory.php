<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\MediaFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaFolder>
 */
class MediaFolderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'brand_id' => Brand::factory(),
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    /**
     * Associate the folder with a specific brand.
     */
    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    /**
     * Set a parent folder.
     */
    public function withParent(MediaFolder $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'brand_id' => $parent->brand_id,
        ]);
    }
}
