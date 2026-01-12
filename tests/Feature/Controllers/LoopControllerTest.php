<?php

use App\Enums\DayOfWeek;
use App\Enums\SocialPlatform;
use App\Models\Brand;
use App\Models\Loop;
use App\Models\LoopItem;
use App\Models\LoopSchedule;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('social.manage', 'web');
    Permission::findOrCreate('social.publish', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'social.manage',
        'social.publish',
    ]);
});

function setupLoopUserWithPermissions(User $user): void
{
    $account = $user->accounts()->first();
    if ($account) {
        setPermissionsTeamId($account->id);
        $user->assignRole('admin');
    }
}

test('guests cannot access loops index', function () {
    $response = $this->get(route('loops.index'));

    $response->assertRedirect('/login');
});

test('users with brand can view loops index', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    Loop::factory()->forBrand($brand)->count(3)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->get(route('loops.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Loops/Index')
        ->has('loops', 3)
    );
});

test('users can view the create loop page', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->get(route('loops.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Loops/Create')
        ->has('platforms')
        ->has('daysOfWeek')
    );
});

test('users can create a loop', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('loops.store'), [
        'name' => 'My Loop',
        'description' => 'Test description',
        'platforms' => ['instagram', 'linkedin'],
        'is_active' => true,
        'schedules' => [
            [
                'day_of_week' => DayOfWeek::Monday->value,
                'time_of_day' => '09:00',
                'platform' => null,
            ],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loops', [
        'brand_id' => $brand->id,
        'name' => 'My Loop',
        'description' => 'Test description',
    ]);
    $this->assertDatabaseHas('loop_schedules', [
        'day_of_week' => DayOfWeek::Monday->value,
        'time_of_day' => '09:00',
    ]);
});

test('loop name is required', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('loops.store'), [
        'platforms' => ['instagram'],
    ]);

    $response->assertSessionHasErrors('name');
});

test('loop platforms are required', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('loops.store'), [
        'name' => 'My Loop',
    ]);

    $response->assertSessionHasErrors('platforms');
});

test('users can view a loop', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();
    LoopItem::factory()->forLoop($loop)->count(2)->create();
    LoopSchedule::factory()->forLoop($loop)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->get(route('loops.show', $loop));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Loops/Show')
        ->has('loop')
        ->has('availableSocialPosts')
        ->has('platforms')
        ->has('daysOfWeek')
    );
});

test('users cannot view loops from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupLoopUserWithPermissions($user);

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $loop = Loop::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->get(route('loops.show', $loop));

    $response->assertForbidden();
});

test('users can view the edit loop page', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->get(route('loops.edit', $loop));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Loops/Edit')
        ->has('loop')
        ->has('platforms')
        ->has('daysOfWeek')
    );
});

test('users can update a loop', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create(['name' => 'Original Name']);

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->put(route('loops.update', $loop), [
        'name' => 'Updated Name',
        'platforms' => ['facebook'],
        'schedules' => [
            [
                'day_of_week' => DayOfWeek::Friday->value,
                'time_of_day' => '15:00',
            ],
        ],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loops', [
        'id' => $loop->id,
        'name' => 'Updated Name',
    ]);
});

test('users cannot update loops from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupLoopUserWithPermissions($user);

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $loop = Loop::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->put(route('loops.update', $loop), [
        'name' => 'Hacked Name',
        'platforms' => ['linkedin'],
    ]);

    $response->assertForbidden();
});

test('users can delete a loop', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->delete(route('loops.destroy', $loop));

    $response->assertRedirect(route('loops.index'));
    $this->assertDatabaseMissing('loops', ['id' => $loop->id]);
});

test('users cannot delete loops from other brands', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupLoopUserWithPermissions($user);

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $loop = Loop::factory()->forBrand($otherBrand)->create();

    $response = $this->actingAs($user)->delete(route('loops.destroy', $loop));

    $response->assertForbidden();
});

test('users can add a standalone item to a loop', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('loops.items.store', $loop), [
        'content' => 'Loop item content',
        'format' => 'feed',
        'hashtags' => ['tag1', 'tag2'],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loop_items', [
        'loop_id' => $loop->id,
        'content' => 'Loop item content',
    ]);
});

test('users can add a linked social post item to a loop', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('loops.items.store', $loop), [
        'social_post_id' => $socialPost->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loop_items', [
        'loop_id' => $loop->id,
        'social_post_id' => $socialPost->id,
    ]);
});

test('users can update a standalone loop item', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();
    $item = LoopItem::factory()->forLoop($loop)->create(['content' => 'Original content']);

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->put(route('loops.items.update', [$loop, $item]), [
        'content' => 'Updated content',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loop_items', [
        'id' => $item->id,
        'content' => 'Updated content',
    ]);
});

test('users cannot update linked loop items', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();
    $socialPost = SocialPost::factory()->forBrand($brand)->create();
    $item = LoopItem::factory()->forLoop($loop)->linkedToSocialPost($socialPost)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->put(route('loops.items.update', [$loop, $item]), [
        'content' => 'Updated content',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('users can remove an item from a loop', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();
    $item = LoopItem::factory()->forLoop($loop)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->delete(route('loops.items.destroy', [$loop, $item]));

    $response->assertRedirect();
    $this->assertDatabaseMissing('loop_items', ['id' => $item->id]);
});

test('removing an item reorders remaining items', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();
    LoopItem::factory()->forLoop($loop)->atPosition(0)->create();
    $itemToRemove = LoopItem::factory()->forLoop($loop)->atPosition(1)->create();
    $item3 = LoopItem::factory()->forLoop($loop)->atPosition(2)->create();

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->delete(route('loops.items.destroy', [$loop, $itemToRemove]));

    $response->assertRedirect();
    expect($item3->refresh()->position)->toBe(1);
});

test('users can reorder loop items', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();
    $item1 = LoopItem::factory()->forLoop($loop)->atPosition(0)->create();
    $item2 = LoopItem::factory()->forLoop($loop)->atPosition(1)->create();
    $item3 = LoopItem::factory()->forLoop($loop)->atPosition(2)->create();

    setupLoopUserWithPermissions($user);

    // Reorder: item3 first, then item1, then item2
    $response = $this->actingAs($user)->post(route('loops.reorder', $loop), [
        'items' => [$item3->id, $item1->id, $item2->id],
    ]);

    $response->assertRedirect();
    expect($item3->refresh()->position)->toBe(0);
    expect($item1->refresh()->position)->toBe(1);
    expect($item2->refresh()->position)->toBe(2);
});

test('users can toggle loop active state', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create(['is_active' => true]);

    setupLoopUserWithPermissions($user);

    $response = $this->actingAs($user)->post(route('loops.toggle', $loop));

    $response->assertRedirect();
    expect($loop->refresh()->is_active)->toBeFalse();

    // Toggle again
    $response = $this->actingAs($user)->post(route('loops.toggle', $loop));

    expect($loop->refresh()->is_active)->toBeTrue();
});

test('users can import items from csv', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    setupLoopUserWithPermissions($user);

    $csvContent = "content,format,hashtags,link\n";
    $csvContent .= "\"First post content\",feed,\"tag1,tag2\",https://example.com\n";
    $csvContent .= "\"Second post content\",story,\"tag3\",\n";

    $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('loops.import', $loop), [
        'file' => $file,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('loop_items', [
        'loop_id' => $loop->id,
        'content' => 'First post content',
    ]);
    $this->assertDatabaseHas('loop_items', [
        'loop_id' => $loop->id,
        'content' => 'Second post content',
    ]);
});

test('csv import skips empty content rows', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $loop = Loop::factory()->forBrand($brand)->create();

    setupLoopUserWithPermissions($user);

    $csvContent = "content,format,hashtags,link\n";
    $csvContent .= "\"Valid content\",feed,,\n";
    $csvContent .= ",feed,,\n";  // Empty content row

    $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

    $response = $this->actingAs($user)->post(route('loops.import', $loop), [
        'file' => $file,
    ]);

    $response->assertRedirect();
    expect($loop->items()->count())->toBe(1);
});
