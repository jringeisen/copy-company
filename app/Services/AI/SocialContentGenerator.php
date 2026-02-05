<?php

namespace App\Services\AI;

use App\Models\Brand;
use App\Models\Post;
use App\Services\SocialPlatforms\PlatformFactory;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

class SocialContentGenerator
{
    public function __construct(
        protected PromptBuilder $promptBuilder
    ) {}

    /**
     * Convert a blog post to social media posts for multiple platforms.
     *
     * @param  array<string>  $platforms
     * @return array<string, array<string, mixed>>
     */
    public function atomize(Brand $brand, Post $post, array $platforms): array
    {
        $results = [];
        $systemPrompt = $this->promptBuilder->build($brand, 'atomize_social');

        foreach ($platforms as $platformIdentifier) {
            if (! PlatformFactory::isSupported($platformIdentifier)) {
                continue;
            }

            $results[$platformIdentifier] = $this->generateForPlatform(
                $platformIdentifier,
                $post,
                $systemPrompt
            );
        }

        return $results;
    }

    /**
     * Generate content for a single platform.
     *
     * @return array<string, mixed>
     */
    protected function generateForPlatform(string $platformIdentifier, Post $post, string $systemPrompt): array
    {
        $platform = PlatformFactory::make($platformIdentifier);

        $userPrompt = "Convert the following blog post into a {$platform->getDisplayName()} post.\n\n";
        $userPrompt .= "Blog Post Title: {$post->title}\n\n";
        $userPrompt .= "Blog Post Content:\n{$post->content_html}\n\n";
        $userPrompt .= "Platform Guidelines:\n{$platform->getAiPromptInstructions()}\n\n";
        $userPrompt .= "Character limit: {$platform->getCharacterLimit()} characters.\n\n";
        $userPrompt .= 'Important: Return ONLY the social post content, no explanations or meta-commentary.';

        try {
            $content = $this->complete($systemPrompt, $userPrompt);

            return [
                'platform' => $platformIdentifier,
                'content' => $content,
                'hashtags' => $this->extractHashtags($content),
                'character_count' => strlen($content),
                'character_limit' => $platform->getCharacterLimit(),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to atomize for {$platformIdentifier}: ".$e->getMessage());

            return [
                'platform' => $platformIdentifier,
                'error' => 'Failed to generate content',
            ];
        }
    }

    /**
     * Extract hashtags from content.
     *
     * @return array<string>
     */
    protected function extractHashtags(string $content): array
    {
        $hashtags = [];
        if (preg_match_all('/#(\w+)/', $content, $matches)) {
            $hashtags = $matches[1];
        }

        return $hashtags;
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
            Log::error('Social Content Generator error: '.$e->getMessage());
            throw $e;
        }
    }
}
