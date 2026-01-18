<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

class SelectionEditor
{
    /**
     * Instruction to preserve markdown formatting in AI responses.
     */
    private const FORMATTING_INSTRUCTION = 'IMPORTANT: The input contains markdown formatting that you MUST preserve exactly. This includes: block-level structure (# Heading 1, ## Heading 2, ### Heading 3, paragraphs separated by blank lines) and inline formatting (**bold**, _italic_, ~~strikethrough~~, `code`, [links](url)). Return the same number of blocks in the same order with the same heading levels. Only modify the text content, not the formatting structure.';

    /**
     * Fix grammar and spelling errors in the text.
     */
    public function fixGrammar(string $text): string
    {
        $systemPrompt = 'You are an expert editor. Fix any grammar, spelling, and punctuation errors in the text. Only return the corrected text, nothing else. Preserve the original meaning and style. '.self::FORMATTING_INSTRUCTION;

        $userPrompt = "Fix grammar and spelling in the following text:\n\n{$text}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    /**
     * Simplify the text to make it easier to understand.
     */
    public function simplify(string $text): string
    {
        $systemPrompt = 'You are an expert editor who simplifies complex text. Make the text easier to understand by using simpler words and shorter sentences. Only return the simplified text, nothing else. '.self::FORMATTING_INSTRUCTION;

        $userPrompt = "Simplify the following text to make it easier to understand:\n\n{$text}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    /**
     * Rephrase the text while keeping the same meaning.
     */
    public function rephrase(string $text): string
    {
        $systemPrompt = 'You are an expert writer. Rephrase the text using different words and sentence structure while keeping the same meaning. Only return the rephrased text, nothing else. '.self::FORMATTING_INSTRUCTION;

        $userPrompt = "Rephrase the following text while keeping the same meaning:\n\n{$text}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    /**
     * Convert the text into a bullet point list.
     */
    public function toList(string $text): string
    {
        $systemPrompt = 'You are an expert editor. Convert the text into a clear, well-organized bullet point list. Use markdown bullet points (- item). Only return the bullet list, nothing else.';

        $userPrompt = "Convert the following text into a bullet point list:\n\n{$text}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    /**
     * Add examples to illustrate the text.
     */
    public function addExamples(string $text): string
    {
        $systemPrompt = 'You are an expert writer. Add 1-2 relevant, concrete examples to illustrate and support the text. Keep the original text and naturally integrate the examples. Only return the enhanced text, nothing else.';

        $userPrompt = "Add illustrative examples to the following text:\n\n{$text}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    /**
     * Complete the AI request.
     */
    protected function complete(string $systemPrompt, string $userPrompt): string
    {
        try {
            $response = Prism::text()
                ->using(config('ai.provider', 'openai'), config('ai.model', 'gpt-4o'))
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt)
                ->withMaxTokens(config('ai.max_tokens', 4096))
                ->asText();

            return $response->text;
        } catch (\Exception $e) {
            Log::error('Selection Editor error: '.$e->getMessage());
            throw $e;
        }
    }
}
