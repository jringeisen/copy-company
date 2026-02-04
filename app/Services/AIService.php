<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Post;
use App\Services\AI\BlogContentGenerator;
use App\Services\AI\ContentSprintGenerator;
use App\Services\AI\MarketingStrategyGenerator;
use App\Services\AI\SocialContentGenerator;

/**
 * Facade service for AI content generation.
 *
 * Delegates to focused generators for specific content types.
 * Use this service when you need access to multiple generators
 * or when you want a simple unified interface.
 *
 * For direct injection of specific generators, use:
 * - BlogContentGenerator for blog content operations
 * - SocialContentGenerator for social media atomization
 * - ContentSprintGenerator for content idea generation
 */
class AIService
{
    public function __construct(
        protected BlogContentGenerator $blogGenerator,
        protected SocialContentGenerator $socialGenerator,
        protected ContentSprintGenerator $sprintGenerator,
        protected MarketingStrategyGenerator $strategyGenerator
    ) {}

    public function generateDraft(Brand $brand, string $title, ?string $bullets = null): string
    {
        return $this->blogGenerator->generateDraft($brand, $title, $bullets);
    }

    public function polishWriting(Brand $brand, string $content): string
    {
        return $this->blogGenerator->polishWriting($brand, $content);
    }

    public function continueWriting(Brand $brand, string $contentSoFar): string
    {
        return $this->blogGenerator->continueWriting($brand, $contentSoFar);
    }

    public function suggestOutline(Brand $brand, string $title, ?string $notes = null): string
    {
        return $this->blogGenerator->suggestOutline($brand, $title, $notes);
    }

    public function changeTone(Brand $brand, string $content, string $targetTone): string
    {
        return $this->blogGenerator->changeTone($brand, $content, $targetTone);
    }

    public function makeItShorter(Brand $brand, string $content): string
    {
        return $this->blogGenerator->makeItShorter($content);
    }

    public function makeItLonger(Brand $brand, string $content): string
    {
        return $this->blogGenerator->makeItLonger($brand, $content);
    }

    public function freeformQuestion(Brand $brand, string $content, string $question): string
    {
        return $this->blogGenerator->freeformQuestion($brand, $content, $question);
    }

    /**
     * @param  array<string>  $platforms
     * @return array<string, array<string, mixed>>
     */
    public function atomizeToSocial(Brand $brand, Post $post, array $platforms): array
    {
        return $this->socialGenerator->atomize($brand, $post, $platforms);
    }

    /**
     * @param  array<string>  $topics
     * @return array<array<string, mixed>>
     */
    public function generateContentSprintIdeas(Brand $brand, array $topics, string $goals = '', int $count = 20): array
    {
        return $this->sprintGenerator->generate($brand, $topics, $goals, $count);
    }

    /**
     * Generate a weekly marketing strategy for a brand.
     *
     * @return array{strategy: array<string, mixed>, context: array<string, mixed>}
     */
    public function generateMarketingStrategy(Brand $brand): array
    {
        return $this->strategyGenerator->generate($brand);
    }
}
