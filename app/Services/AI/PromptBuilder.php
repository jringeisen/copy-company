<?php

namespace App\Services\AI;

use App\Models\AiPrompt;
use App\Models\Brand;

class PromptBuilder
{
    /**
     * @var array<string, string>
     */
    protected array $defaultPrompts = [
        'blog_draft' => 'You are a skilled content writer who helps creators write engaging blog posts. Write in a conversational, authentic tone that connects with readers.',
        'polish_writing' => "You are an expert editor who improves writing while preserving the author's unique voice. Focus on clarity, flow, and engagement.",
        'continue_writing' => 'You are a writing assistant who continues content naturally. Match the existing tone and style perfectly.',
        'suggest_outline' => 'You are a content strategist who creates comprehensive blog post outlines. Focus on logical flow and reader engagement.',
        'change_tone' => 'You are an expert at adapting writing tone while preserving the core message and information.',
        'expand_content' => 'You are a skilled writer who expands content with relevant details, examples, and insights.',
        'freeform' => 'You are a helpful writing assistant who provides thoughtful guidance on content creation.',
        'atomize_social' => "You are a social media expert who transforms blog content into engaging social media posts. You understand each platform's unique voice, format, and audience expectations. Create content that feels native to each platform while maintaining the core message. Do not include emojis in your output - leave emoji usage to the user's discretion.",
        'content_sprint' => 'You are an expert content strategist who generates creative, engaging blog post ideas. You understand what makes content valuable, shareable, and SEO-friendly. Generate diverse ideas that provide real value to readers while supporting business goals.',
        'marketing_strategy' => 'You are an expert marketing strategist who creates weekly content plans for brands. You understand content marketing, social media strategy, email marketing, content loops/recycling, and how different content channels work together. Create strategies that are cohesive, actionable, and tailored to the brand\'s voice and industry. Each week\'s strategy should build on previous work and avoid repeating topics.',
    ];

    /**
     * Build a system prompt for the given type and brand.
     */
    public function build(Brand $brand, string $type): string
    {
        $systemPrompt = $this->getBasePrompt($type, $brand->industry);
        $systemPrompt = $this->appendBrandVoice($systemPrompt, $brand);
        $systemPrompt = $this->appendIndustryContext($systemPrompt, $brand);

        return $systemPrompt;
    }

    /**
     * Get the base system prompt for a type.
     */
    protected function getBasePrompt(string $type, ?string $industry): string
    {
        // Try to get a stored prompt first
        $storedPrompt = AiPrompt::getPrompt($type, $industry);

        if ($storedPrompt) {
            return $storedPrompt->system_prompt;
        }

        return $this->defaultPrompts[$type] ?? 'You are a helpful writing assistant.';
    }

    /**
     * Append brand voice guidelines to the prompt.
     */
    protected function appendBrandVoice(string $prompt, Brand $brand): string
    {
        if (! $brand->voice_settings) {
            return $prompt;
        }

        $prompt .= "\n\nBrand Voice Guidelines:";

        if (isset($brand->voice_settings['tone'])) {
            $prompt .= "\nTone: {$brand->voice_settings['tone']}";
        }

        if (isset($brand->voice_settings['style'])) {
            $prompt .= "\nStyle: {$brand->voice_settings['style']}";
        }

        if (! empty($brand->voice_settings['sample_texts'])) {
            $prompt .= "\n\nExample of the brand's writing style:";
            foreach ($brand->voice_settings['sample_texts'] as $sample) {
                $prompt .= "\n---\n{$sample}\n---";
            }
        }

        return $prompt;
    }

    /**
     * Append industry context to the prompt.
     */
    protected function appendIndustryContext(string $prompt, Brand $brand): string
    {
        if ($brand->industry) {
            $prompt .= "\n\nIndustry context: {$brand->industry}";
        }

        return $prompt;
    }

    /**
     * Build a simple editor prompt without brand context.
     */
    public function buildEditorPrompt(): string
    {
        return 'You are an expert editor. Your task is to make content more concise while keeping the essential message.';
    }
}
