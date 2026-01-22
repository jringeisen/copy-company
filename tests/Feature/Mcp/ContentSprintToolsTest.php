<?php

use App\Enums\ContentSprintStatus;
use App\Jobs\GenerateContentSprint;
use App\Mcp\Servers\CopyCompanyServer;
use App\Mcp\Tools\ContentSprints\AcceptSprintIdeasTool;
use App\Mcp\Tools\ContentSprints\GetSprintTool;
use App\Mcp\Tools\ContentSprints\ListSprintsTool;
use App\Mcp\Tools\ContentSprints\TriggerContentSprintTool;
use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('sprints.create', 'web');
    Permission::findOrCreate('sprints.manage', 'web');
    Permission::findOrCreate('posts.create', 'web');

    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->givePermissionTo([
        'sprints.create',
        'sprints.manage',
        'posts.create',
    ]);

    config([
        'services.stripe.prices.starter_monthly' => 'price_starter_monthly',
        'services.stripe.prices.starter_annual' => 'price_starter_annual',
        'services.stripe.prices.creator_monthly' => 'price_creator_monthly',
        'services.stripe.prices.creator_annual' => 'price_creator_annual',
        'services.stripe.prices.pro_monthly' => 'price_pro_monthly',
        'services.stripe.prices.pro_annual' => 'price_pro_annual',
    ]);
});

function setupMcpSprintUser(User $user): void
{
    $account = $user->accounts()->first();
    if ($account) {
        setPermissionsTeamId($account->id);
        $user->assignRole('admin');
    }
    session(['current_brand_id' => $user->currentBrand()?->id]);
}

test('TriggerContentSprintTool dispatches sprint job', function () {
    Queue::fake();

    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(TriggerContentSprintTool::class, [
        'topics' => ['marketing', 'social media'],
        'goals' => 'Increase engagement',
        'content_count' => 10,
    ]);

    $response->assertOk();

    Queue::assertPushed(GenerateContentSprint::class);
});

test('TriggerContentSprintTool requires topics', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(TriggerContentSprintTool::class, [
        'goals' => 'Some goals',
    ]);

    $response->assertHasErrors();
});

test('TriggerContentSprintTool limits topics to 10', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupMcpSprintUser($user);

    $topics = [];
    for ($i = 0; $i < 15; $i++) {
        $topics[] = "Topic {$i}";
    }

    $response = CopyCompanyServer::actingAs($user)->tool(TriggerContentSprintTool::class, [
        'topics' => $topics,
    ]);

    $response->assertHasErrors();
});

test('ListSprintsTool returns sprints for current brand', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    ContentSprint::factory()->count(3)->forBrand($brand)->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(ListSprintsTool::class, []);

    $response->assertOk();
});

test('ListSprintsTool filters by status', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();

    ContentSprint::factory()->forBrand($brand)->create(['status' => ContentSprintStatus::Pending]);
    ContentSprint::factory()->forBrand($brand)->completed()->create();
    ContentSprint::factory()->forBrand($brand)->create(['status' => ContentSprintStatus::Failed]);

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(ListSprintsTool::class, [
        'status' => 'completed',
    ]);

    $response->assertOk();
});

test('GetSprintTool returns sprint with ideas', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()->forBrand($brand)->completed()->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(GetSprintTool::class, [
        'sprint_id' => $sprint->id,
    ]);

    $response->assertOk();
});

test('GetSprintTool returns error for non-existent sprint', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(GetSprintTool::class, [
        'sprint_id' => 99999,
    ]);

    $response->assertHasErrors();
});

test('GetSprintTool cannot access other brand sprints', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $otherUser = User::factory()->create();
    $otherBrand = Brand::factory()->forUser($otherUser)->create();
    $otherSprint = ContentSprint::factory()->forBrand($otherBrand)->completed()->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(GetSprintTool::class, [
        'sprint_id' => $otherSprint->id,
    ]);

    $response->assertHasErrors();
});

test('AcceptSprintIdeasTool creates draft posts from ideas', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()->forBrand($brand)->completed()->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(AcceptSprintIdeasTool::class, [
        'sprint_id' => $sprint->id,
        'idea_indices' => [0, 1],
    ]);

    $response->assertOk();

    expect($brand->posts()->count())->toBe(2);
});

test('AcceptSprintIdeasTool requires completed sprint', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()->forBrand($brand)->create([
        'status' => ContentSprintStatus::Generating,
    ]);

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(AcceptSprintIdeasTool::class, [
        'sprint_id' => $sprint->id,
        'idea_indices' => [0],
    ]);

    $response->assertHasErrors();
});

test('AcceptSprintIdeasTool rejects invalid idea indices', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()->forBrand($brand)->completed()->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(AcceptSprintIdeasTool::class, [
        'sprint_id' => $sprint->id,
        'idea_indices' => [99, 100],
    ]);

    $response->assertHasErrors();
});

test('AcceptSprintIdeasTool skips already converted ideas', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->completed()
        ->withConvertedIdeas([0])
        ->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(AcceptSprintIdeasTool::class, [
        'sprint_id' => $sprint->id,
        'idea_indices' => [0, 1],
    ]);

    $response->assertOk();

    // Only 1 new post should be created (index 1), since index 0 was already converted
    expect($brand->posts()->count())->toBe(1);
});

test('AcceptSprintIdeasTool errors when all ideas already converted', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()
        ->forBrand($brand)
        ->completed()
        ->withConvertedIdeas([0, 1])
        ->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(AcceptSprintIdeasTool::class, [
        'sprint_id' => $sprint->id,
        'idea_indices' => [0, 1],
    ]);

    $response->assertHasErrors();
});

test('AcceptSprintIdeasTool requires idea_indices', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $sprint = ContentSprint::factory()->forBrand($brand)->completed()->create();

    setupMcpSprintUser($user);

    $response = CopyCompanyServer::actingAs($user)->tool(AcceptSprintIdeasTool::class, [
        'sprint_id' => $sprint->id,
    ]);

    $response->assertHasErrors();
});
