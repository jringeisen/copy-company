<?php

use App\Models\Brand;
use App\Models\User;
use App\Services\AI\BlogContentGenerator;
use App\Services\AI\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->brand = Brand::factory()->forUser($this->user)->create();
    $this->promptBuilder = new PromptBuilder;
    $this->generator = new BlogContentGenerator($this->promptBuilder);
});

function createTextResponse(string $text): TextResponse
{
    return new TextResponse(
        steps: collect([]),
        text: $text,
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(100, 200),
        meta: new Meta('fake', 'fake'),
        messages: collect([]),
        additionalContent: [],
    );
}

test('it generates draft content', function () {
    Prism::fake([
        createTextResponse('Generated blog post content about AI'),
    ]);

    $result = $this->generator->generateDraft($this->brand, 'Introduction to AI');

    expect($result)->toBe('Generated blog post content about AI');
});

test('it generates draft with bullets', function () {
    Prism::fake([
        createTextResponse('Content with bullet points'),
    ]);

    $result = $this->generator->generateDraft($this->brand, 'Title', '- Point 1\n- Point 2');

    expect($result)->toBe('Content with bullet points');
});

test('it polishes writing', function () {
    Prism::fake([
        createTextResponse('Polished content'),
    ]);

    $result = $this->generator->polishWriting($this->brand, 'Original content');

    expect($result)->toBe('Polished content');
});

test('it continues writing', function () {
    Prism::fake([
        createTextResponse('Continued content...'),
    ]);

    $result = $this->generator->continueWriting($this->brand, 'Content so far...');

    expect($result)->toBe('Continued content...');
});

test('it suggests outline', function () {
    Prism::fake([
        createTextResponse('1. Introduction\n2. Main Body\n3. Conclusion'),
    ]);

    $result = $this->generator->suggestOutline($this->brand, 'My Topic');

    expect($result)->toContain('Introduction');
});

test('it suggests outline with notes', function () {
    Prism::fake([
        createTextResponse('Outline with notes'),
    ]);

    $result = $this->generator->suggestOutline($this->brand, 'Title', 'Some notes');

    expect($result)->toBe('Outline with notes');
});

test('it changes tone', function () {
    Prism::fake([
        createTextResponse('Formal content'),
    ]);

    $result = $this->generator->changeTone($this->brand, 'Casual content', 'formal');

    expect($result)->toBe('Formal content');
});

test('it makes content shorter', function () {
    Prism::fake([
        createTextResponse('Shorter content'),
    ]);

    $result = $this->generator->makeItShorter('Long content here...');

    expect($result)->toBe('Shorter content');
});

test('it makes content longer', function () {
    Prism::fake([
        createTextResponse('Much longer content with more details...'),
    ]);

    $result = $this->generator->makeItLonger($this->brand, 'Short content');

    expect($result)->toBe('Much longer content with more details...');
});

test('it answers freeform questions', function () {
    Prism::fake([
        createTextResponse('Answer to your question'),
    ]);

    $result = $this->generator->freeformQuestion($this->brand, 'My content', 'How can I make this more engaging?');

    expect($result)->toBe('Answer to your question');
});
