<?php

use App\Enums\NewsletterSendStatus;
use App\Models\Account;
use App\Models\Brand;
use App\Models\EmailEvent;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('guests cannot access newsletters index', function () {
    $response = $this->get(route('newsletters.index'));

    $response->assertRedirect('/login');
});

test('users without brand are redirected to create brand from newsletters index', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.index'));

    $response->assertRedirect(route('brands.create'));
});

test('users without brand are redirected to create brand from newsletter show', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);

    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherPost = Post::factory()->forBrand($otherBrand)->create();
    $newsletter = NewsletterSend::factory()->create([
        'brand_id' => $otherBrand->id,
        'post_id' => $otherPost->id,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.show', $newsletter));

    $response->assertRedirect(route('brands.create'));
});

test('users with brand can view newsletters index', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    $post = Post::factory()->forBrand($brand)->create();
    NewsletterSend::factory()->count(3)->create([
        'brand_id' => $brand->id,
        'post_id' => $post->id,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Newsletters/Index')
        ->has('newsletters.data', 3)
    );
});

test('newsletters index shows empty state when no newsletters', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Newsletters/Index')
        ->has('newsletters.data', 0)
    );
});

test('newsletters index only shows newsletters for current brand', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    // Create newsletters for current brand
    $post = Post::factory()->forBrand($brand)->create();
    NewsletterSend::factory()->count(2)->create([
        'brand_id' => $brand->id,
        'post_id' => $post->id,
    ]);

    // Create newsletters for another brand (should not appear)
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherPost = Post::factory()->forBrand($otherBrand)->create();
    NewsletterSend::factory()->count(3)->create([
        'brand_id' => $otherBrand->id,
        'post_id' => $otherPost->id,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Newsletters/Index')
        ->has('newsletters.data', 2)
    );
});

test('users can view newsletter show page', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    $post = Post::factory()->forBrand($brand)->create();
    $newsletter = NewsletterSend::factory()->create([
        'brand_id' => $brand->id,
        'post_id' => $post->id,
        'status' => NewsletterSendStatus::Sent,
        'recipients_count' => 100,
        'sent_count' => 98,
        'unique_opens' => 45,
        'unique_clicks' => 12,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.show', $newsletter));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Newsletters/Show')
        ->has('newsletter')
        ->has('eventCounts')
        ->where('newsletter.id', $newsletter->id)
        ->where('newsletter.subject_line', $newsletter->subject_line)
    );
});

test('newsletter show page includes event counts', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    $post = Post::factory()->forBrand($brand)->create();
    $newsletter = NewsletterSend::factory()->create([
        'brand_id' => $brand->id,
        'post_id' => $post->id,
        'status' => NewsletterSendStatus::Sent,
    ]);

    // Create some email events
    EmailEvent::factory()->count(5)->create([
        'newsletter_send_id' => $newsletter->id,
        'event_type' => 'delivery',
    ]);
    EmailEvent::factory()->count(3)->create([
        'newsletter_send_id' => $newsletter->id,
        'event_type' => 'open',
    ]);
    EmailEvent::factory()->count(1)->create([
        'newsletter_send_id' => $newsletter->id,
        'event_type' => 'click',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.show', $newsletter));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Newsletters/Show')
        ->where('eventCounts.delivery', 5)
        ->where('eventCounts.open', 3)
        ->where('eventCounts.click', 1)
    );
});

test('users cannot view newsletters from other brands', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    Brand::factory()->forAccount($account)->create();

    // Create newsletter for another brand
    $otherAccount = Account::factory()->create();
    $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
    $otherPost = Post::factory()->forBrand($otherBrand)->create();
    $newsletter = NewsletterSend::factory()->create([
        'brand_id' => $otherBrand->id,
        'post_id' => $otherPost->id,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.show', $newsletter));

    $response->assertNotFound();
});

test('newsletter resource includes computed open and click rates', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    $post = Post::factory()->forBrand($brand)->create();
    $newsletter = NewsletterSend::factory()->create([
        'brand_id' => $brand->id,
        'post_id' => $post->id,
        'status' => NewsletterSendStatus::Sent,
        'recipients_count' => 100,
        'unique_opens' => 50,
        'unique_clicks' => 25,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.show', $newsletter));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Newsletters/Show')
        ->where('newsletter.open_rate', 50)
        ->where('newsletter.click_rate', 25)
    );
});

test('newsletters index is paginated', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => 'admin']);
    $brand = Brand::factory()->forAccount($account)->create();

    $post = Post::factory()->forBrand($brand)->create();
    NewsletterSend::factory()->count(20)->create([
        'brand_id' => $brand->id,
        'post_id' => $post->id,
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_account_id' => $account->id])
        ->get(route('newsletters.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Newsletters/Index')
        ->has('newsletters.data', 15) // Uses Inertia::scroll() with pagination
    );
});
