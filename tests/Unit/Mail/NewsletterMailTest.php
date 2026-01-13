<?php

use App\Mail\NewsletterMail;
use App\Models\Brand;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create([
        'name' => 'Test Brand',
        'slug' => 'test-brand',
    ]);
    $this->post = Post::factory()->forBrand($this->brand)->create();
    $this->subscriber = Subscriber::factory()->forBrand($this->brand)->confirmed()->create([
        'email' => 'subscriber@example.com',
    ]);
    $this->newsletterSend = NewsletterSend::factory()
        ->forPost($this->post)
        ->forBrand($this->brand)
        ->create([
            'subject_line' => 'Test Newsletter Subject',
            'preview_text' => 'Preview text here',
        ]);
});

test('it sets correct subject line', function () {
    $mail = new NewsletterMail($this->newsletterSend, $this->subscriber);

    $envelope = $mail->envelope();

    expect($envelope->subject)->toBe('Test Newsletter Subject');
});

test('it sets correct from address', function () {
    $mail = new NewsletterMail($this->newsletterSend, $this->subscriber);

    $envelope = $mail->envelope();

    expect($envelope->from->address)->toBe($this->brand->getEmailFromAddress())
        ->and($envelope->from->name)->toBe($this->brand->getEmailFromName());
});

test('it includes correct content data', function () {
    $mail = new NewsletterMail($this->newsletterSend, $this->subscriber);

    $content = $mail->content();

    expect($content->view)->toBe('emails.newsletter')
        ->and($content->with['post']->id)->toBe($this->post->id)
        ->and($content->with['brand']->id)->toBe($this->brand->id)
        ->and($content->with['subscriber']->id)->toBe($this->subscriber->id)
        ->and($content->with['previewText'])->toBe('Preview text here');
});

test('it includes unsubscribe url', function () {
    $mail = new NewsletterMail($this->newsletterSend, $this->subscriber);

    $content = $mail->content();

    $expectedUrl = route('public.subscribe.unsubscribe', [
        'brand' => $this->brand->slug,
        'token' => $this->subscriber->unsubscribe_token,
    ]);

    expect($content->with['unsubscribeUrl'])->toBe($expectedUrl);
});

test('it can be rendered', function () {
    $mail = new NewsletterMail($this->newsletterSend, $this->subscriber);

    // Just ensure it renders without error
    $mail->render();

    expect(true)->toBeTrue();
});
