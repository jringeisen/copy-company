<?php

use App\Models\Brand;
use App\Models\Post;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;

uses(RefreshDatabase::class);

test('guests cannot access ai endpoints', function () {
    $this->postJson(route('ai.draft'), ['title' => 'Test'])
        ->assertUnauthorized();
});

test('users without brand cannot use ai draft', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('ai.draft'), ['title' => 'Test'])
        ->assertForbidden();
});

test('draft requires title', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->postJson(route('ai.draft'), [])
        ->assertJsonValidationErrorFor('title');
});

test('draft generates content successfully', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('generateDraft')
        ->once()
        ->andReturn('Generated draft content');

    $response = $this->actingAs($user)
        ->postJson(route('ai.draft'), [
            'title' => 'My Blog Post',
            'bullets' => 'Point 1, Point 2',
        ]);

    $response->assertOk()
        ->assertJson(['content' => 'Generated draft content']);
});

test('polish requires content', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->postJson(route('ai.polish'), [])
        ->assertJsonValidationErrorFor('content');
});

test('polish improves content successfully', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('polishWriting')
        ->once()
        ->andReturn('Polished content');

    $response = $this->actingAs($user)
        ->postJson(route('ai.polish'), ['content' => 'Original content']);

    $response->assertOk()
        ->assertJson(['content' => 'Polished content']);
});

test('continue writing requires content', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->postJson(route('ai.continue'), [])
        ->assertJsonValidationErrorFor('content');
});

test('continue writing generates more content', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('continueWriting')
        ->once()
        ->andReturn('Continued content here...');

    $response = $this->actingAs($user)
        ->postJson(route('ai.continue'), ['content' => 'Started writing...']);

    $response->assertOk()
        ->assertJson(['content' => 'Continued content here...']);
});

test('outline generates suggestions successfully', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('suggestOutline')
        ->once()
        ->andReturn('1. Introduction\n2. Main Points\n3. Conclusion');

    $response = $this->actingAs($user)
        ->postJson(route('ai.outline'), [
            'title' => 'My Blog Topic',
            'notes' => 'Some notes',
        ]);

    $response->assertOk()
        ->assertJsonStructure(['content']);
});

test('change tone modifies content tone', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('changeTone')
        ->once()
        ->andReturn('Content with new tone');

    $response = $this->actingAs($user)
        ->postJson(route('ai.tone'), [
            'content' => 'Original content',
            'tone' => 'professional',
        ]);

    $response->assertOk()
        ->assertJson(['content' => 'Content with new tone']);
});

test('shorter makes content concise', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('makeItShorter')
        ->once()
        ->andReturn('Shorter version');

    $response = $this->actingAs($user)
        ->postJson(route('ai.shorter'), ['content' => 'Long content here...']);

    $response->assertOk()
        ->assertJson(['content' => 'Shorter version']);
});

test('longer expands content', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('makeItLonger')
        ->once()
        ->andReturn('Much longer expanded content...');

    $response = $this->actingAs($user)
        ->postJson(route('ai.longer'), ['content' => 'Short content']);

    $response->assertOk()
        ->assertJson(['content' => 'Much longer expanded content...']);
});

test('ask answers questions about content', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('freeformQuestion')
        ->once()
        ->andReturn('Answer to the question');

    $response = $this->actingAs($user)
        ->postJson(route('ai.ask'), [
            'content' => 'Some content here',
            'question' => 'What can I improve?',
        ]);

    $response->assertOk()
        ->assertJson(['content' => 'Answer to the question']);
});

test('ask requires both content and question', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->postJson(route('ai.ask'), ['content' => 'Just content'])
        ->assertJsonValidationErrorFor('question');

    $this->actingAs($user)
        ->postJson(route('ai.ask'), ['question' => 'Just question'])
        ->assertJsonValidationErrorFor('content');
});

test('atomize generates social posts from blog post', function () {
    $user = User::factory()->create();
    $brand = Brand::factory()->forUser($user)->create();
    $post = Post::factory()->forBrand($brand)->create();

    $this->mock(AIService::class)
        ->shouldReceive('atomizeToSocial')
        ->once()
        ->andReturn([
            'twitter' => ['content' => 'Tweet content', 'hashtags' => ['#blog']],
            'instagram' => ['content' => 'Instagram caption', 'hashtags' => ['#post']],
        ]);

    $response = $this->actingAs($user)
        ->postJson(route('ai.atomize'), [
            'post_id' => $post->id,
            'platforms' => ['twitter', 'instagram'],
        ]);

    $response->assertOk()
        ->assertJsonStructure(['posts']);
});

test('connection exception returns 503 error', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('generateDraft')
        ->once()
        ->andThrow(new ConnectionException('Could not connect'));

    $response = $this->actingAs($user)
        ->postJson(route('ai.draft'), ['title' => 'Test Title']);

    $response->assertStatus(503)
        ->assertJson(['error' => 'Unable to connect to AI service. Please try again.']);
});

test('rate limit exception returns appropriate error message', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('generateDraft')
        ->once()
        ->andThrow(new \Exception('rate limit exceeded'));

    $response = $this->actingAs($user)
        ->postJson(route('ai.draft'), ['title' => 'Test Title']);

    $response->assertStatus(500)
        ->assertJson(['error' => 'AI service rate limit reached. Please wait a moment and try again.']);
});

test('timeout exception returns appropriate error message', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('generateDraft')
        ->once()
        ->andThrow(new \Exception('Request timed out'));

    $response = $this->actingAs($user)
        ->postJson(route('ai.draft'), ['title' => 'Test Title']);

    $response->assertStatus(500)
        ->assertJson(['error' => 'The AI service took too long to respond. Please try again.']);
});

test('generic exception returns 500 error', function () {
    $user = User::factory()->create();
    Brand::factory()->forUser($user)->create();

    $this->mock(AIService::class)
        ->shouldReceive('generateDraft')
        ->once()
        ->andThrow(new \Exception('Unknown error'));

    $response = $this->actingAs($user)
        ->postJson(route('ai.draft'), ['title' => 'Test Title']);

    $response->assertStatus(500)
        ->assertJsonStructure(['error']);
});
