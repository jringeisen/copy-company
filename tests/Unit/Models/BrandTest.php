<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\Post;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('brand belongs to an account', function () {
    $brand = Brand::factory()->create();

    expect($brand->account)->toBeInstanceOf(Account::class);
});

test('brand has many posts', function () {
    $brand = Brand::factory()->create();
    Post::factory()->count(3)->forBrand($brand)->create();

    expect($brand->posts)->toHaveCount(3);
});

test('brand has many subscribers', function () {
    $brand = Brand::factory()->create();
    Subscriber::factory()->count(5)->forBrand($brand)->create();

    expect($brand->subscribers)->toHaveCount(5);
});

test('brand url uses custom domain when verified', function () {
    $brand = Brand::factory()->withCustomDomain('example.com')->create();

    expect($brand->url)->toBe('https://example.com');
});

test('brand url uses app url with slug when no custom domain', function () {
    $brand = Brand::factory()->create(['slug' => 'test-brand']);

    expect($brand->url)->toBe(config('app.url').'/@test-brand');
});

test('active subscribers count only includes confirmed subscribers', function () {
    $brand = Brand::factory()->create();
    Subscriber::factory()->count(3)->confirmed()->forBrand($brand)->create();
    Subscriber::factory()->count(2)->pending()->forBrand($brand)->create();
    Subscriber::factory()->count(1)->unsubscribed()->forBrand($brand)->create();

    expect($brand->active_subscribers_count)->toBe(3);
});

test('getEmailFromAddress uses custom email domain when verified', function () {
    $brand = Brand::factory()->create([
        'custom_email_domain' => 'myemail.com',
        'custom_email_from' => 'newsletter',
        'email_domain_verification_status' => 'verified',
    ]);

    expect($brand->getEmailFromAddress())->toBe('newsletter@myemail.com');
});

test('getEmailFromAddress uses hello as default from when custom_email_from is empty', function () {
    $brand = Brand::factory()->create([
        'custom_email_domain' => 'myemail.com',
        'custom_email_from' => null,
        'email_domain_verification_status' => 'verified',
    ]);

    expect($brand->getEmailFromAddress())->toBe('hello@myemail.com');
});

test('getEmailFromAddress returns default mail address when email domain not verified', function () {
    $brand = Brand::factory()->create([
        'custom_email_domain' => 'myemail.com',
        'email_domain_verification_status' => 'pending',
    ]);

    expect($brand->getEmailFromAddress())->toBe(config('mail.from.address'));
});

test('hasVerifiedEmailDomain returns true when domain is verified', function () {
    $brand = Brand::factory()->create([
        'custom_email_domain' => 'myemail.com',
        'email_domain_verification_status' => 'verified',
    ]);

    expect($brand->hasVerifiedEmailDomain())->toBeTrue();
});

test('hasVerifiedEmailDomain returns false when domain is not verified', function () {
    $brand = Brand::factory()->create([
        'custom_email_domain' => 'myemail.com',
        'email_domain_verification_status' => 'pending',
    ]);

    expect($brand->hasVerifiedEmailDomain())->toBeFalse();
});

test('hasVerifiedEmailDomain returns false when custom_email_domain is null', function () {
    $brand = Brand::factory()->create([
        'custom_email_domain' => null,
        'email_domain_verification_status' => 'verified',
    ]);

    expect($brand->hasVerifiedEmailDomain())->toBeFalse();
});
