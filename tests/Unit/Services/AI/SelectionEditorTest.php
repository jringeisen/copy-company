<?php

use App\Services\AI\SelectionEditor;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function () {
    $this->editor = new SelectionEditor;
});

function createSelectionTextResponse(string $text): TextResponse
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

test('fixGrammar returns corrected text', function () {
    Prism::fake([
        createSelectionTextResponse('This is **corrected** text.'),
    ]);

    $result = $this->editor->fixGrammar('This are **corrected** text.');

    expect($result)->toBe('This is **corrected** text.');
});

test('fixGrammar preserves markdown in response', function () {
    Prism::fake([
        createSelectionTextResponse('This is **bold** and _italic_ text.'),
    ]);

    $result = $this->editor->fixGrammar('This are **bold** and _italic_ text.');

    expect($result)->toContain('**bold**')
        ->toContain('_italic_');
});

test('simplify returns simplified text', function () {
    Prism::fake([
        createSelectionTextResponse('Simple text.'),
    ]);

    $result = $this->editor->simplify('Complex and convoluted text.');

    expect($result)->toBe('Simple text.');
});

test('simplify preserves markdown in response', function () {
    Prism::fake([
        createSelectionTextResponse('**Simple** text.'),
    ]);

    $result = $this->editor->simplify('**Complex** text.');

    expect($result)->toContain('**Simple**');
});

test('rephrase returns rephrased text', function () {
    Prism::fake([
        createSelectionTextResponse('Rephrased content here.'),
    ]);

    $result = $this->editor->rephrase('Original content here.');

    expect($result)->toBe('Rephrased content here.');
});

test('rephrase preserves links in response', function () {
    Prism::fake([
        createSelectionTextResponse('Check [this link](https://example.com) for more info.'),
    ]);

    $result = $this->editor->rephrase('Visit [this link](https://example.com) for details.');

    expect($result)->toContain('[this link](https://example.com)');
});

test('toList converts text to bullet list', function () {
    Prism::fake([
        createSelectionTextResponse("- Item one\n- Item two"),
    ]);

    $result = $this->editor->toList('Item one and item two.');

    expect($result)->toContain('- Item one');
});

test('addExamples adds examples to text', function () {
    Prism::fake([
        createSelectionTextResponse('Original text. For example, this illustrates the point.'),
    ]);

    $result = $this->editor->addExamples('Original text.');

    expect($result)->toContain('For example');
});

test('formatting instruction constant includes all markdown types', function () {
    $reflection = new ReflectionClass(SelectionEditor::class);
    $constant = $reflection->getConstant('FORMATTING_INSTRUCTION');

    expect($constant)->toContain('**bold**')
        ->toContain('_italic_')
        ->toContain('~~strikethrough~~')
        ->toContain('`code`')
        ->toContain('[links](url)')
        ->toContain('# Heading 1')
        ->toContain('## Heading 2')
        ->toContain('### Heading 3');
});
