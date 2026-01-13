<?php

use App\Models\AiPrompt;
use App\Models\Brand;
use App\Models\User;
use App\Services\AI\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create([
        'name' => 'Test Brand',
        'industry' => 'Technology',
    ]);
    $this->promptBuilder = new PromptBuilder;
});

test('it returns default prompt for known type', function () {
    $prompt = $this->promptBuilder->build($this->brand, 'blog_draft');

    expect($prompt)->toContain('skilled content writer');
});

test('it returns fallback prompt for unknown type', function () {
    $prompt = $this->promptBuilder->build($this->brand, 'unknown_type');

    expect($prompt)->toContain('helpful writing assistant');
});

test('it appends industry context', function () {
    $prompt = $this->promptBuilder->build($this->brand, 'blog_draft');

    expect($prompt)->toContain('Industry context: Technology');
});

test('it appends brand voice tone', function () {
    $this->brand->update([
        'voice_settings' => [
            'tone' => 'Professional and authoritative',
        ],
    ]);

    $prompt = $this->promptBuilder->build($this->brand, 'blog_draft');

    expect($prompt)->toContain('Brand Voice Guidelines')
        ->and($prompt)->toContain('Tone: Professional and authoritative');
});

test('it appends brand voice style', function () {
    $this->brand->update([
        'voice_settings' => [
            'style' => 'Conversational with technical depth',
        ],
    ]);

    $prompt = $this->promptBuilder->build($this->brand, 'blog_draft');

    expect($prompt)->toContain('Style: Conversational with technical depth');
});

test('it appends sample texts', function () {
    $this->brand->update([
        'voice_settings' => [
            'sample_texts' => [
                'This is a sample of our writing.',
                'Another example of our voice.',
            ],
        ],
    ]);

    $prompt = $this->promptBuilder->build($this->brand, 'blog_draft');

    expect($prompt)->toContain("Example of the brand's writing style")
        ->and($prompt)->toContain('This is a sample of our writing.')
        ->and($prompt)->toContain('Another example of our voice.');
});

test('it does not append voice guidelines when null', function () {
    $this->brand->update(['voice_settings' => null]);

    $prompt = $this->promptBuilder->build($this->brand, 'blog_draft');

    expect($prompt)->not->toContain('Brand Voice Guidelines');
});

test('it uses stored prompt when available', function () {
    AiPrompt::create([
        'type' => 'blog_draft',
        'industry' => 'Technology',
        'system_prompt' => 'Custom prompt for technology blogs.',
        'user_prompt_template' => 'Write a blog post about {topic}',
        'is_active' => true,
    ]);

    $prompt = $this->promptBuilder->build($this->brand, 'blog_draft');

    expect($prompt)->toContain('Custom prompt for technology blogs.');
});

test('it builds editor prompt without brand context', function () {
    $prompt = $this->promptBuilder->buildEditorPrompt();

    expect($prompt)->toBe('You are an expert editor. Your task is to make content more concise while keeping the essential message.');
});

test('it skips industry context when not set', function () {
    $this->brand->update(['industry' => null]);

    $prompt = $this->promptBuilder->build($this->brand, 'blog_draft');

    expect($prompt)->not->toContain('Industry context:');
});

test('it has default prompts for all expected types', function () {
    $types = [
        'blog_draft',
        'polish_writing',
        'continue_writing',
        'suggest_outline',
        'change_tone',
        'expand_content',
        'freeform',
        'atomize_social',
        'content_sprint',
    ];

    foreach ($types as $type) {
        $prompt = $this->promptBuilder->build($this->brand, $type);
        expect($prompt)->not->toBe('You are a helpful writing assistant.');
    }
});
