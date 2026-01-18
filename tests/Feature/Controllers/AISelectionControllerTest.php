<?php

use App\Models\Brand;
use App\Models\User;
use App\Services\AI\SelectionEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Brand::factory()->forUser($this->user)->create();
});

test('guests cannot access selection endpoints', function () {
    $this->postJson(route('ai.selection.fix-grammar'), ['text' => 'Test'])
        ->assertUnauthorized();
});

test('users without brand cannot use selection tools', function () {
    $userWithoutBrand = User::factory()->create();

    $this->actingAs($userWithoutBrand)
        ->postJson(route('ai.selection.fix-grammar'), ['text' => 'Test'])
        ->assertForbidden();
});

test('fix grammar requires text', function () {
    $this->actingAs($this->user)
        ->postJson(route('ai.selection.fix-grammar'), [])
        ->assertJsonValidationErrorFor('text');
});

test('fix grammar returns corrected content', function () {
    $this->mock(SelectionEditor::class)
        ->shouldReceive('fixGrammar')
        ->once()
        ->with('This are **bold** text.')
        ->andReturn('This is **bold** text.');

    $response = $this->actingAs($this->user)
        ->postJson(route('ai.selection.fix-grammar'), [
            'text' => 'This are **bold** text.',
        ]);

    $response->assertOk()
        ->assertJson(['content' => 'This is **bold** text.']);
});

test('fix grammar preserves markdown formatting in input', function () {
    $textWithMarkdown = 'This are **bold** and _italic_ text with a [link](https://example.com).';

    $this->mock(SelectionEditor::class)
        ->shouldReceive('fixGrammar')
        ->once()
        ->withArgs(function ($text) use ($textWithMarkdown) {
            return $text === $textWithMarkdown;
        })
        ->andReturn('This is **bold** and _italic_ text with a [link](https://example.com).');

    $response = $this->actingAs($this->user)
        ->postJson(route('ai.selection.fix-grammar'), [
            'text' => $textWithMarkdown,
        ]);

    $response->assertOk();
});

test('simplify requires text', function () {
    $this->actingAs($this->user)
        ->postJson(route('ai.selection.simplify'), [])
        ->assertJsonValidationErrorFor('text');
});

test('simplify returns simplified content', function () {
    $this->mock(SelectionEditor::class)
        ->shouldReceive('simplify')
        ->once()
        ->andReturn('Simple text.');

    $response = $this->actingAs($this->user)
        ->postJson(route('ai.selection.simplify'), [
            'text' => 'Complex and convoluted text.',
        ]);

    $response->assertOk()
        ->assertJson(['content' => 'Simple text.']);
});

test('rephrase requires text', function () {
    $this->actingAs($this->user)
        ->postJson(route('ai.selection.rephrase'), [])
        ->assertJsonValidationErrorFor('text');
});

test('rephrase returns rephrased content', function () {
    $this->mock(SelectionEditor::class)
        ->shouldReceive('rephrase')
        ->once()
        ->andReturn('Rephrased content.');

    $response = $this->actingAs($this->user)
        ->postJson(route('ai.selection.rephrase'), [
            'text' => 'Original content.',
        ]);

    $response->assertOk()
        ->assertJson(['content' => 'Rephrased content.']);
});

test('to list requires text', function () {
    $this->actingAs($this->user)
        ->postJson(route('ai.selection.to-list'), [])
        ->assertJsonValidationErrorFor('text');
});

test('to list returns bullet list', function () {
    $this->mock(SelectionEditor::class)
        ->shouldReceive('toList')
        ->once()
        ->andReturn("- Item one\n- Item two");

    $response = $this->actingAs($this->user)
        ->postJson(route('ai.selection.to-list'), [
            'text' => 'Item one and item two.',
        ]);

    $response->assertOk()
        ->assertJson(['content' => "- Item one\n- Item two"]);
});

test('add examples requires text', function () {
    $this->actingAs($this->user)
        ->postJson(route('ai.selection.add-examples'), [])
        ->assertJsonValidationErrorFor('text');
});

test('add examples returns enhanced content', function () {
    $this->mock(SelectionEditor::class)
        ->shouldReceive('addExamples')
        ->once()
        ->andReturn('Original text. For example, this illustrates the point.');

    $response = $this->actingAs($this->user)
        ->postJson(route('ai.selection.add-examples'), [
            'text' => 'Original text.',
        ]);

    $response->assertOk()
        ->assertJsonFragment(['content' => 'Original text. For example, this illustrates the point.']);
});

test('connection exception returns 503 error', function () {
    $this->mock(SelectionEditor::class)
        ->shouldReceive('fixGrammar')
        ->once()
        ->andThrow(new \Illuminate\Http\Client\ConnectionException('Could not connect'));

    $response = $this->actingAs($this->user)
        ->postJson(route('ai.selection.fix-grammar'), ['text' => 'Test text']);

    $response->assertStatus(503)
        ->assertJson(['error' => 'Unable to connect to AI service. Please try again.']);
});

test('rate limit exception returns appropriate error message', function () {
    $this->mock(SelectionEditor::class)
        ->shouldReceive('fixGrammar')
        ->once()
        ->andThrow(new \Exception('rate limit exceeded'));

    $response = $this->actingAs($this->user)
        ->postJson(route('ai.selection.fix-grammar'), ['text' => 'Test text']);

    $response->assertStatus(500)
        ->assertJson(['error' => 'AI service rate limit reached. Please wait a moment and try again.']);
});
