<?php

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('brand resource transforms brand correctly', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create([
        'name' => 'Test Brand',
        'slug' => 'test-brand',
        'tagline' => 'A test tagline',
        'description' => 'A test description',
        'industry' => 'technology',
        'primary_color' => '#ff0000',
        'secondary_color' => '#00ff00',
    ]);

    $resource = new BrandResource($brand);
    $array = $resource->toArray(app(Request::class));

    expect($array)->toHaveKey('id', $brand->id)
        ->toHaveKey('name', 'Test Brand')
        ->toHaveKey('slug', 'test-brand')
        ->toHaveKey('tagline', 'A test tagline')
        ->toHaveKey('description', 'A test description')
        ->toHaveKey('industry', 'technology')
        ->toHaveKey('primary_color', '#ff0000')
        ->toHaveKey('secondary_color', '#00ff00');
});

test('brand resource includes url attribute', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create([
        'slug' => 'my-brand',
    ]);

    $resource = new BrandResource($brand);
    $array = $resource->toArray(app(Request::class));

    expect($array)->toHaveKey('url');
    expect($array['url'])->toContain('@my-brand');
});

test('brand resource formats dates correctly', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $resource = new BrandResource($brand);
    $array = $resource->toArray(app(Request::class));

    expect($array['created_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
    expect($array['updated_at'])->toMatch('/^[A-Z][a-z]{2} \d{2}, \d{4} \d{1,2}:\d{2} [AP]M$/');
});

test('brand resource converts newsletter provider to value', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $resource = new BrandResource($brand);
    $array = $resource->toArray(app(Request::class));

    expect($array['newsletter_provider'])->toBeString();
});
