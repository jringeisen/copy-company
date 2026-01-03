<?php

namespace App\Services\AI;

use App\Models\Brand;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

class ContentSprintGenerator
{
    public function __construct(
        protected PromptBuilder $promptBuilder
    ) {}

    /**
     * Generate content sprint ideas for a brand.
     *
     * @param  array<string>  $topics
     * @return array<array<string, mixed>>
     */
    public function generate(Brand $brand, array $topics, string $goals = '', int $count = 20): array
    {
        $systemPrompt = $this->promptBuilder->build($brand, 'content_sprint');

        $topicsList = implode(', ', $topics);

        $userPrompt = "Generate {$count} unique blog post ideas based on the following topics: {$topicsList}\n\n";

        if ($goals) {
            $userPrompt .= "Content Goals: {$goals}\n\n";
        }

        $userPrompt .= $this->getIdeasFormat();

        $response = $this->complete($systemPrompt, $userPrompt);

        return $this->parseResponse($response);
    }

    /**
     * Get the expected format for the ideas JSON.
     */
    protected function getIdeasFormat(): string
    {
        return 'For each idea, provide:
1. A compelling title
2. A brief description (2-3 sentences)
3. 3-5 key points to cover
4. Estimated word count (500-2000 words)

Return the ideas as a JSON array with this structure:
[
  {
    "title": "The blog post title",
    "description": "Brief description of what the post will cover",
    "key_points": ["Point 1", "Point 2", "Point 3"],
    "estimated_words": 1200
  }
]

Important:
- Make titles specific and engaging
- Ensure variety across the ideas
- Focus on practical, actionable content
- Return ONLY valid JSON, no markdown formatting or code blocks';
    }

    /**
     * Parse the AI response into an array of ideas.
     *
     * @return array<array<string, mixed>>
     */
    protected function parseResponse(string $response): array
    {
        // Clean up the response - remove any markdown code blocks
        $cleanedResponse = preg_replace('/^```json\s*/', '', $response);
        $cleanedResponse = preg_replace('/\s*```$/', '', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $ideas = json_decode($cleanedResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse content sprint ideas JSON: '.json_last_error_msg(), [
                'response' => $response,
            ]);
            throw new \Exception('Failed to parse AI response as JSON');
        }

        return $ideas;
    }

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
            Log::error('Content Sprint Generator error: '.$e->getMessage());
            throw $e;
        }
    }
}
