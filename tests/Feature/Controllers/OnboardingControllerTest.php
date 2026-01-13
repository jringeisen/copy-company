<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create();
    $this->account->users()->attach($this->user->id, ['role' => 'admin']);
    $this->brand = Brand::factory()->forAccount($this->account)->create();
});

test('user can mark onboarding step as complete', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_account_id' => $this->account->id])
        ->postJson(route('onboarding.step', 'calendar_viewed'));

    $response->assertOk()
        ->assertJson(['success' => true]);

    $this->brand->refresh();
    expect($this->brand->onboarding_status['calendar_viewed'])->toBeTrue();
});

test('marking invalid step returns error', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_account_id' => $this->account->id])
        ->postJson(route('onboarding.step', 'invalid_step'));

    $response->assertStatus(400)
        ->assertJson(['error' => 'Invalid step']);
});

test('marking step without brand returns error', function () {
    $userWithoutBrand = User::factory()->create();

    $response = $this->actingAs($userWithoutBrand)
        ->postJson(route('onboarding.step', 'calendar_viewed'));

    $response->assertStatus(404)
        ->assertJson(['error' => 'No brand found']);
});

test('user can dismiss onboarding', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['current_account_id' => $this->account->id])
        ->post(route('onboarding.dismiss'));

    $response->assertRedirect();

    $this->brand->refresh();
    expect($this->brand->onboarding_dismissed)->toBeTrue();
});

test('dismiss without brand does not cause error', function () {
    $userWithoutBrand = User::factory()->create();

    $response = $this->actingAs($userWithoutBrand)
        ->post(route('onboarding.dismiss'));

    $response->assertRedirect();
});
