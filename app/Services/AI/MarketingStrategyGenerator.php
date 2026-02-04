<?php

namespace App\Services\AI;

use App\Models\Brand;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

class MarketingStrategyGenerator
{
    public function __construct(
        protected PromptBuilder $promptBuilder
    ) {}

    /**
     * Generate a weekly marketing strategy for a brand.
     *
     * @return array{strategy: array<string, mixed>, context: array<string, mixed>}
     */
    public function generate(Brand $brand): array
    {
        $context = $this->gatherContext($brand);
        $systemPrompt = $this->promptBuilder->build($brand, 'marketing_strategy');
        $userPrompt = $this->buildUserPrompt($brand, $context);

        $response = $this->complete($systemPrompt, $userPrompt);
        $strategy = $this->parseResponse($response);

        return [
            'strategy' => $strategy,
            'context' => $context,
        ];
    }

    /**
     * Gather contextual data about the brand for the AI prompt.
     *
     * @return array<string, mixed>
     */
    protected function gatherContext(Brand $brand): array
    {
        $previousStrategies = $brand->marketingStrategies()
            ->completed()
            ->orderByDesc('week_start')
            ->limit(4)
            ->get(['strategy_content'])
            ->map(fn ($strategy) => [
                'week_theme' => $strategy->strategy_content['week_theme']['title'] ?? null,
                'talking_points' => $strategy->strategy_content['talking_points'] ?? [],
            ])
            ->toArray();

        $recentPosts = $brand->posts()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(10)
            ->pluck('title')
            ->toArray();

        $recentSocialPosts = $brand->socialPosts()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get(['content'])
            ->map(fn ($post) => mb_substr($post->content ?? '', 0, 100))
            ->toArray();

        $recentNewsletterSubjects = $brand->newsletterSends()
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('subject_line')
            ->toArray();

        $connectedPlatforms = collect($brand->social_connections ?? [])
            ->keys()
            ->toArray();

        $subscriberCount = $brand->subscribers()->confirmed()->count();

        $activeLoops = $brand->loops()
            ->where('is_active', true)
            ->with(['items' => fn ($q) => $q->limit(3)])
            ->get()
            ->map(fn ($loop) => [
                'name' => $loop->name,
                'description' => $loop->description,
                'platforms' => $loop->platforms,
                'sample_items' => $loop->items->map(fn ($item) => mb_substr($item->content ?? '', 0, 100))->toArray(),
            ])
            ->toArray();

        return [
            'brand_name' => $brand->name,
            'industry' => $brand->industry,
            'description' => $brand->description,
            'tagline' => $brand->tagline,
            'strategy_context' => $brand->strategy_context,
            'previous_strategies' => $previousStrategies,
            'recent_posts' => $recentPosts,
            'recent_social_posts' => $recentSocialPosts,
            'recent_newsletter_subjects' => $recentNewsletterSubjects,
            'connected_platforms' => $connectedPlatforms,
            'subscriber_count' => $subscriberCount,
            'active_loops' => $activeLoops,
        ];
    }

    /**
     * Build the user prompt with context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function buildUserPrompt(Brand $brand, array $context): string
    {
        $prompt = "Create a weekly marketing strategy for \"{$context['brand_name']}\"";

        if ($context['industry']) {
            $prompt .= " in the {$context['industry']} industry";
        }

        $prompt .= ".\n\n";

        if ($context['description']) {
            $prompt .= "Brand Description: {$context['description']}\n";
        }

        if ($context['tagline']) {
            $prompt .= "Tagline: {$context['tagline']}\n";
        }

        if ($context['strategy_context']) {
            $prompt .= "\nMARKETING CONTEXT:\n{$context['strategy_context']}\n";
        }

        $prompt .= "\n";

        // Previous strategies to avoid repetition
        if (! empty($context['previous_strategies'])) {
            $prompt .= "PREVIOUS WEEKLY THEMES (avoid repeating these):\n";
            foreach ($context['previous_strategies'] as $prev) {
                if ($prev['week_theme']) {
                    $prompt .= "- {$prev['week_theme']}\n";
                }
            }
            $prompt .= "\n";
        }

        // Recent content
        if (! empty($context['recent_posts'])) {
            $prompt .= "RECENT BLOG POSTS (build on these, don't repeat):\n";
            foreach ($context['recent_posts'] as $title) {
                $prompt .= "- {$title}\n";
            }
            $prompt .= "\n";
        }

        if (! empty($context['recent_social_posts'])) {
            $prompt .= "RECENT SOCIAL POSTS:\n";
            foreach ($context['recent_social_posts'] as $content) {
                $prompt .= "- {$content}\n";
            }
            $prompt .= "\n";
        }

        if (! empty($context['recent_newsletter_subjects'])) {
            $prompt .= "RECENT NEWSLETTER SUBJECTS:\n";
            foreach ($context['recent_newsletter_subjects'] as $subject) {
                $prompt .= "- {$subject}\n";
            }
            $prompt .= "\n";
        }

        // Platform context
        if (! empty($context['connected_platforms'])) {
            $platforms = implode(', ', $context['connected_platforms']);
            $prompt .= "CONNECTED SOCIAL PLATFORMS: {$platforms}\n";
            $prompt .= "Generate social posts ONLY for these connected platforms.\n\n";
        } else {
            $prompt .= "No social platforms connected. Generate social posts for instagram and facebook as suggestions.\n\n";
        }

        if ($context['subscriber_count'] > 0) {
            $prompt .= "NEWSLETTER SUBSCRIBER COUNT: {$context['subscriber_count']}\n\n";
        }

        // Active loops
        if (! empty($context['active_loops'])) {
            $prompt .= "EXISTING ACTIVE LOOPS (suggest complementary new loops, or skip if well-covered):\n";
            foreach ($context['active_loops'] as $loop) {
                $prompt .= "- {$loop['name']}: {$loop['description']}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= $this->getResponseFormat();

        return $prompt;
    }

    /**
     * Get the expected JSON response format.
     */
    protected function getResponseFormat(): string
    {
        return 'Return your strategy as a JSON object with this exact structure:
{
  "week_theme": {
    "title": "Theme title",
    "description": "Brief description of the week\'s focus"
  },
  "blog_posts": [
    {
      "title": "Post title",
      "description": "What this post covers",
      "key_points": ["Point 1", "Point 2", "Point 3"],
      "estimated_words": 1200,
      "suggested_day": "Tuesday",
      "rationale": "Why this post matters this week"
    }
  ],
  "newsletter": {
    "subject_line": "Email subject",
    "topic": "What the newsletter covers",
    "key_points": ["Point 1", "Point 2"],
    "suggested_day": "Thursday"
  },
  "social_posts": [
    {
      "platform": "instagram",
      "content": "Post caption/text",
      "hashtags": ["tag1", "tag2"],
      "suggested_day": "Monday",
      "post_type": "educational"
    }
  ],
  "loops": [
    {
      "name": "Loop name",
      "description": "What this loop does",
      "platforms": ["instagram", "facebook"],
      "suggested_items": [
        {
          "content": "Item content text",
          "hashtags": ["tag1", "tag2"]
        }
      ],
      "rationale": "Why this loop is valuable"
    }
  ],
  "talking_points": [
    "Key message 1 for the week",
    "Key message 2 for the week"
  ]
}

Important guidelines:
- Generate 2-3 blog post ideas
- Generate 3-5 social posts across connected platforms
- Generate 1 newsletter plan
- Generate 0-2 loop suggestions (only if they complement existing loops)
- Generate 3-5 talking points
- Make all content cohesive around the week theme
- Ensure variety in content types and approaches
- Return ONLY valid JSON, no markdown formatting or code blocks';
    }

    /**
     * Parse the AI response into a strategy array.
     *
     * @return array<string, mixed>
     */
    protected function parseResponse(string $response): array
    {
        $cleanedResponse = preg_replace('/^```json\s*/', '', $response);
        $cleanedResponse = preg_replace('/\s*```$/', '', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        $strategy = json_decode($cleanedResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse marketing strategy JSON: '.json_last_error_msg(), [
                'response' => $response,
            ]);
            throw new \Exception('Failed to parse AI response as JSON');
        }

        return $strategy;
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
            Log::error('Marketing Strategy Generator error: '.$e->getMessage());
            throw $e;
        }
    }
}
