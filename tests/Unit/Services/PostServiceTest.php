<?php

use App\Enums\NewsletterSendStatus;
use App\Enums\PostStatus;
use App\Jobs\ProcessNewsletterSend;
use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new PostService;
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
});

// ==========================================
// CREATE TESTS
// ==========================================

test('create creates a new post with all required fields', function () {
    $validated = [
        'title' => 'Test Post Title',
        'content' => [['type' => 'paragraph', 'content' => 'Test content']],
        'content_html' => '<p>Test content</p>',
    ];

    $post = $this->service->create($this->brand, $this->user->id, $validated);

    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->title)->toBe('Test Post Title')
        ->and($post->slug)->toBe('test-post-title')
        ->and($post->content)->toBe([['type' => 'paragraph', 'content' => 'Test content']])
        ->and($post->status)->toBe(PostStatus::Draft)
        ->and($post->brand_id)->toBe($this->brand->id)
        ->and($post->user_id)->toBe($this->user->id);
});

test('create sets optional fields when provided', function () {
    $validated = [
        'title' => 'Test Post',
        'content' => [],
        'content_html' => '<p>Content</p>',
        'excerpt' => 'Custom excerpt',
        'featured_image' => 'https://example.com/image.jpg',
        'seo_title' => 'SEO Title',
        'seo_description' => 'SEO Description',
        'tags' => ['tag1', 'tag2'],
        'publish_to_blog' => false,
        'send_as_newsletter' => false,
        'generate_social' => false,
    ];

    $post = $this->service->create($this->brand, $this->user->id, $validated);

    expect($post->excerpt)->toBe('Custom excerpt')
        ->and($post->featured_image)->toBe('https://example.com/image.jpg')
        ->and($post->seo_title)->toBe('SEO Title')
        ->and($post->seo_description)->toBe('SEO Description')
        ->and($post->tags)->toBe(['tag1', 'tag2'])
        ->and($post->publish_to_blog)->toBeFalse()
        ->and($post->send_as_newsletter)->toBeFalse()
        ->and($post->generate_social)->toBeFalse();
});

test('create sanitizes html content', function () {
    $validated = [
        'title' => 'Test Post',
        'content' => [],
        'content_html' => '<p onclick="alert(1)">Test <script>evil()</script></p>',
    ];

    $post = $this->service->create($this->brand, $this->user->id, $validated);

    expect($post->content_html)->not->toContain('<script>')
        ->and($post->content_html)->not->toContain('onclick');
});

// ==========================================
// UPDATE TESTS
// ==========================================

test('update updates post with new data', function () {
    $post = Post::factory()->forBrand($this->brand)->create([
        'title' => 'Original Title',
    ]);

    $validated = [
        'title' => 'Updated Title',
        'content' => [['type' => 'paragraph', 'content' => 'Updated']],
        'content_html' => '<p>Updated</p>',
        'excerpt' => 'New excerpt',
    ];

    $updatedPost = $this->service->update($post, $validated);

    expect($updatedPost->title)->toBe('Updated Title')
        ->and($updatedPost->excerpt)->toBe('New excerpt');
});

test('update sanitizes html content', function () {
    $post = Post::factory()->forBrand($this->brand)->create();

    $validated = [
        'title' => 'Test',
        'content' => [],
        'content_html' => '<p onmouseover="evil()">Test</p>',
    ];

    $updatedPost = $this->service->update($post, $validated);

    expect($updatedPost->content_html)->not->toContain('onmouseover');
});

// ==========================================
// PUBLISH TESTS
// ==========================================

test('publish updates post status to published', function () {
    Queue::fake();

    $post = Post::factory()->forBrand($this->brand)->draft()->create();

    $this->service->publish($post, [
        'publish_to_blog' => true,
        'send_as_newsletter' => false,
    ]);

    expect($post->fresh()->status)->toBe(PostStatus::Published)
        ->and($post->fresh()->published_at)->not->toBeNull();
});

test('publish creates newsletter send when send_as_newsletter is true', function () {
    Queue::fake();

    $post = Post::factory()->forBrand($this->brand)->draft()->create();

    $this->service->publish($post, [
        'publish_to_blog' => true,
        'send_as_newsletter' => true,
        'subject_line' => 'Test Subject',
        'preview_text' => 'Preview text',
    ]);

    $this->assertDatabaseHas('newsletter_sends', [
        'post_id' => $post->id,
        'subject_line' => 'Test Subject',
        'preview_text' => 'Preview text',
    ]);

    Queue::assertPushed(ProcessNewsletterSend::class);
});

test('publish does not create newsletter when send_as_newsletter is false', function () {
    Queue::fake();

    $post = Post::factory()->forBrand($this->brand)->draft()->create();

    $this->service->publish($post, [
        'publish_to_blog' => true,
        'send_as_newsletter' => false,
    ]);

    $this->assertDatabaseMissing('newsletter_sends', [
        'post_id' => $post->id,
    ]);

    Queue::assertNotPushed(ProcessNewsletterSend::class);
});

// ==========================================
// SCHEDULE TESTS
// ==========================================

test('schedule updates post status to scheduled', function () {
    $post = Post::factory()->forBrand($this->brand)->draft()->create();
    $scheduledAt = now()->addDays(3)->toDateTimeString();

    $this->service->schedule($post, [
        'scheduled_at' => $scheduledAt,
        'publish_to_blog' => true,
        'send_as_newsletter' => false,
    ]);

    $freshPost = $post->fresh();
    expect($freshPost->status)->toBe(PostStatus::Scheduled)
        ->and($freshPost->scheduled_at)->not->toBeNull();
});

test('schedule creates newsletter send with scheduled status', function () {
    $post = Post::factory()->forBrand($this->brand)->draft()->create();
    $scheduledAt = now()->addDays(3);

    $this->service->schedule($post, [
        'scheduled_at' => $scheduledAt->toDateTimeString(),
        'publish_to_blog' => true,
        'send_as_newsletter' => true,
        'subject_line' => 'Scheduled Newsletter',
    ]);

    $this->assertDatabaseHas('newsletter_sends', [
        'post_id' => $post->id,
        'subject_line' => 'Scheduled Newsletter',
        'status' => NewsletterSendStatus::Scheduled->value,
    ]);
});

test('schedule does not dispatch newsletter job immediately', function () {
    Queue::fake();

    $post = Post::factory()->forBrand($this->brand)->draft()->create();

    $this->service->schedule($post, [
        'scheduled_at' => now()->addDays(3)->toDateTimeString(),
        'publish_to_blog' => true,
        'send_as_newsletter' => true,
        'subject_line' => 'Test',
    ]);

    Queue::assertNotPushed(ProcessNewsletterSend::class);
});
