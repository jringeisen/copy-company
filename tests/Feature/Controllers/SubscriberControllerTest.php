<?php

use App\Models\Brand;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('guests cannot access subscribers index', function () {
    $response = $this->get(route('subscribers.index'));

    $response->assertRedirect('/login');
});

test('users with brand can view subscribers index', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Subscriber::factory()->forBrand($brand)->count(5)->create();

    $response = $this->actingAs($user)->get(route('subscribers.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Subscribers/Index')
    );
});

test('users can delete a subscriber', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $subscriber = Subscriber::factory()->forBrand($brand)->create();

    $response = $this->actingAs($user)->delete(route('subscribers.destroy', $subscriber));

    $response->assertRedirect();
    $this->assertDatabaseMissing('subscribers', ['id' => $subscriber->id]);
});

test('users cannot delete subscribers from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $subscriber = Subscriber::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->delete(route('subscribers.destroy', $subscriber));

    $response->assertForbidden();
    $this->assertDatabaseHas('subscribers', ['id' => $subscriber->id]);
});

test('users can export subscribers as csv', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Subscriber::factory()->forBrand($brand)->count(3)->create();

    $response = $this->actingAs($user)->get(route('subscribers.export'));

    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

test('users can import subscribers from csv', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    $csvContent = "email,name\ntest1@example.com,John Doe\ntest2@example.com,Jane Smith";
    $file = UploadedFile::fake()->createWithContent('subscribers.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('subscribers.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('subscribers', [
        'brand_id' => $brand->id,
        'email' => 'test1@example.com',
        'name' => 'John Doe',
    ]);
    $this->assertDatabaseHas('subscribers', [
        'brand_id' => $brand->id,
        'email' => 'test2@example.com',
        'name' => 'Jane Smith',
    ]);
});
