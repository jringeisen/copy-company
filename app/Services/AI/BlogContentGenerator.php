<?php

namespace App\Services\AI;

use App\Models\Brand;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

class BlogContentGenerator
{
    /**
     * Instruction to preserve markdown formatting in AI responses.
     */
    private const FORMATTING_INSTRUCTION = 'IMPORTANT: The input contains markdown formatting that you MUST preserve exactly. This includes: block-level structure (# Heading 1, ## Heading 2, ### Heading 3, paragraphs separated by blank lines) and inline formatting (**bold**, _italic_, ~~strikethrough~~, `code`, [links](url)). Return the same number of blocks in the same order with the same heading levels. Only modify the text content, not the formatting structure.';

    public function __construct(
        protected PromptBuilder $promptBuilder
    ) {}

    public function generateDraft(Brand $brand, string $title, ?string $bullets = null): string
    {
        $systemPrompt = $this->promptBuilder->build($brand, 'blog_draft');

        $userPrompt = "Write a blog post with the following title: \"{$title}\"";

        if ($bullets) {
            $userPrompt .= "\n\nKey points to cover:\n{$bullets}";
        }

        $userPrompt .= "\n\nWrite in a conversational, engaging style. Include relevant examples and practical insights.";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function polishWriting(Brand $brand, string $content): string
    {
        $systemPrompt = $this->promptBuilder->build($brand, 'polish_writing').' '.self::FORMATTING_INSTRUCTION;

        $userPrompt = "Please polish and improve the following content while maintaining the author's voice and intent. Focus on clarity, flow, and engagement. Do not add new information, just improve what's there.\n\nContent:\n{$content}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function continueWriting(Brand $brand, string $contentSoFar): string
    {
        $systemPrompt = $this->promptBuilder->build($brand, 'continue_writing');

        $userPrompt = "Continue writing from where this content leaves off. Match the tone and style. Write 2-3 more paragraphs.\n\nContent so far:\n{$contentSoFar}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function suggestOutline(Brand $brand, string $title, ?string $notes = null): string
    {
        $systemPrompt = $this->promptBuilder->build($brand, 'suggest_outline');

        $userPrompt = "Create a detailed blog post outline for the following topic: \"{$title}\"";

        if ($notes) {
            $userPrompt .= "\n\nAdditional notes/ideas:\n{$notes}";
        }

        $userPrompt .= "\n\nProvide a structured outline with main sections and subsections. Include brief descriptions of what each section should cover.";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function changeTone(Brand $brand, string $content, string $targetTone): string
    {
        $systemPrompt = $this->promptBuilder->build($brand, 'change_tone').' '.self::FORMATTING_INSTRUCTION;

        $userPrompt = "Rewrite the following content to have a more {$targetTone} tone while preserving the core message and information.\n\nOriginal content:\n{$content}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function makeItShorter(string $content): string
    {
        $systemPrompt = $this->promptBuilder->buildEditorPrompt().' '.self::FORMATTING_INSTRUCTION;

        $userPrompt = "Make the following content shorter and more concise. Remove redundancy and unnecessary words while keeping the core message intact.\n\nContent:\n{$content}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function makeItLonger(Brand $brand, string $content): string
    {
        $systemPrompt = $this->promptBuilder->build($brand, 'expand_content');

        $userPrompt = "Expand the following content with more detail, examples, and explanation. Make it more comprehensive while maintaining the same tone.\n\nContent:\n{$content}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function freeformQuestion(Brand $brand, string $content, string $question): string
    {
        $systemPrompt = $this->promptBuilder->build($brand, 'freeform');

        $userPrompt = "Given the following content context:\n\n{$content}\n\nPlease help with: {$question}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    protected function complete(string $systemPrompt, string $userPrompt): string
    {
        try {
            $response = Prism::text()
                ->using(config('ai.provider', 'openai'), config('ai.model', 'gpt-4o'))
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt)
                ->withMaxTokens(config('ai.max_tokens', 4096))
                ->withClientOptions(['timeout' => config('ai.timeout', 120)])
                ->asText();

            return $response->text;
        } catch (\Exception $e) {
            Log::error('Blog Content Generator error: '.$e->getMessage());
            throw $e;
        }
    }
}
