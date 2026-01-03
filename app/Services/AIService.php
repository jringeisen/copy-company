<?php

namespace App\Services;

use App\Models\AiPrompt;
use App\Models\Brand;
use App\Models\Post;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;

class AIService
{
    public function generateDraft(Brand $brand, string $title, ?string $bullets = null): string
    {
        $systemPrompt = $this->buildSystemPrompt($brand, 'blog_draft');

        $userPrompt = "Write a blog post with the following title: \"{$title}\"";

        if ($bullets) {
            $userPrompt .= "\n\nKey points to cover:\n{$bullets}";
        }

        $userPrompt .= "\n\nWrite in a conversational, engaging style. Include relevant examples and practical insights.";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function polishWriting(Brand $brand, string $content): string
    {
        $systemPrompt = $this->buildSystemPrompt($brand, 'polish_writing');

        $userPrompt = "Please polish and improve the following content while maintaining the author's voice and intent. Focus on clarity, flow, and engagement. Do not add new information, just improve what's there.\n\nContent:\n{$content}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function continueWriting(Brand $brand, string $contentSoFar): string
    {
        $systemPrompt = $this->buildSystemPrompt($brand, 'continue_writing');

        $userPrompt = "Continue writing from where this content leaves off. Match the tone and style. Write 2-3 more paragraphs.\n\nContent so far:\n{$contentSoFar}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function suggestOutline(Brand $brand, string $title, ?string $notes = null): string
    {
        $systemPrompt = $this->buildSystemPrompt($brand, 'suggest_outline');

        $userPrompt = "Create a detailed blog post outline for the following topic: \"{$title}\"";

        if ($notes) {
            $userPrompt .= "\n\nAdditional notes/ideas:\n{$notes}";
        }

        $userPrompt .= "\n\nProvide a structured outline with main sections and subsections. Include brief descriptions of what each section should cover.";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function changeTone(Brand $brand, string $content, string $targetTone): string
    {
        $systemPrompt = $this->buildSystemPrompt($brand, 'change_tone');

        $userPrompt = "Rewrite the following content to have a more {$targetTone} tone while preserving the core message and information.\n\nOriginal content:\n{$content}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function makeItShorter(Brand $brand, string $content): string
    {
        $systemPrompt = 'You are an expert editor. Your task is to make content more concise while keeping the essential message.';

        $userPrompt = "Make the following content shorter and more concise. Remove redundancy and unnecessary words while keeping the core message intact.\n\nContent:\n{$content}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function makeItLonger(Brand $brand, string $content): string
    {
        $systemPrompt = $this->buildSystemPrompt($brand, 'expand_content');

        $userPrompt = "Expand the following content with more detail, examples, and explanation. Make it more comprehensive while maintaining the same tone.\n\nContent:\n{$content}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function freeformQuestion(Brand $brand, string $content, string $question): string
    {
        $systemPrompt = $this->buildSystemPrompt($brand, 'freeform');

        $userPrompt = "Given the following content context:\n\n{$content}\n\nPlease help with: {$question}";

        return $this->complete($systemPrompt, $userPrompt);
    }

    public function atomizeToSocial(Brand $brand, Post $post, array $platforms): array
    {
        $results = [];

        $platformConfigs = [
            'instagram' => [
                'name' => 'Instagram',
                'char_limit' => 2200,
                'instructions' => 'Create an engaging Instagram caption. Start with a hook, include relevant emojis sparingly, and end with a call-to-action. Include 5-10 relevant hashtags at the end.',
            ],
            'twitter' => [
                'name' => 'X (Twitter)',
                'char_limit' => 280,
                'instructions' => 'Create a concise, punchy tweet. Make every word count. If the content is too long, create a thread with 2-3 tweets. Use 1-2 relevant hashtags maximum.',
            ],
            'facebook' => [
                'name' => 'Facebook',
                'char_limit' => 63206,
                'instructions' => 'Create a conversational Facebook post that encourages engagement. Ask a question or invite comments. Keep it friendly and accessible.',
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'char_limit' => 3000,
                'instructions' => 'Create a professional LinkedIn post. Start with a strong opening line. Use line breaks for readability. Include insights or takeaways. End with a thought-provoking question.',
            ],
            'pinterest' => [
                'name' => 'Pinterest',
                'char_limit' => 500,
                'instructions' => 'Create a Pinterest pin description. Focus on keywords and searchability. Be descriptive about what value the content provides. Include relevant keywords naturally.',
            ],
            'tiktok' => [
                'name' => 'TikTok',
                'char_limit' => 2200,
                'instructions' => 'Create a TikTok caption. Keep it short, fun, and trendy. Use relevant hashtags including trending ones if applicable. Make it feel authentic and casual.',
            ],
        ];

        $systemPrompt = $this->buildSystemPrompt($brand, 'atomize_social');

        foreach ($platforms as $platform) {
            if (! isset($platformConfigs[$platform])) {
                continue;
            }

            $config = $platformConfigs[$platform];

            $userPrompt = "Convert the following blog post into a {$config['name']} post.\n\n";
            $userPrompt .= "Blog Post Title: {$post->title}\n\n";
            $userPrompt .= "Blog Post Content:\n{$post->content_html}\n\n";
            $userPrompt .= "Platform Guidelines:\n{$config['instructions']}\n\n";
            $userPrompt .= "Character limit: {$config['char_limit']} characters.\n\n";
            $userPrompt .= 'Important: Return ONLY the social post content, no explanations or meta-commentary.';

            try {
                $content = $this->complete($systemPrompt, $userPrompt);

                // Extract hashtags if present
                $hashtags = [];
                if (preg_match_all('/#(\w+)/', $content, $matches)) {
                    $hashtags = $matches[1];
                }

                $results[$platform] = [
                    'platform' => $platform,
                    'content' => $content,
                    'hashtags' => $hashtags,
                    'character_count' => strlen($content),
                    'character_limit' => $config['char_limit'],
                ];
            } catch (\Exception $e) {
                Log::error("Failed to atomize for {$platform}: ".$e->getMessage());
                $results[$platform] = [
                    'platform' => $platform,
                    'error' => 'Failed to generate content',
                ];
            }
        }

        return $results;
    }

    public function generateContentSprintIdeas(Brand $brand, array $topics, string $goals = '', int $count = 20): array
    {
        $systemPrompt = $this->buildSystemPrompt($brand, 'content_sprint');

        $topicsList = implode(', ', $topics);

        $userPrompt = "Generate {$count} unique blog post ideas based on the following topics: {$topicsList}

";

        if ($goals) {
            $userPrompt .= "Content Goals: {$goals}

";
        }

        $userPrompt .= 'For each idea, provide:
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

        $response = $this->complete($systemPrompt, $userPrompt);

        // Parse the JSON response
        try {
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
        } catch (\Exception $e) {
            Log::error('Content sprint generation error: '.$e->getMessage());
            throw $e;
        }
    }

    private function complete(string $systemPrompt, string $userPrompt): string
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
            Log::error('AI Service error: '.$e->getMessage());
            throw $e;
        }
    }

    private function buildSystemPrompt(Brand $brand, string $type): string
    {
        // Try to get a stored prompt first
        $storedPrompt = AiPrompt::getPrompt($type, $brand->industry);

        if ($storedPrompt) {
            $systemPrompt = $storedPrompt->system_prompt;
        } else {
            // Default system prompts
            $systemPrompt = match ($type) {
                'blog_draft' => 'You are a skilled content writer who helps creators write engaging blog posts. Write in a conversational, authentic tone that connects with readers.',
                'polish_writing' => "You are an expert editor who improves writing while preserving the author's unique voice. Focus on clarity, flow, and engagement.",
                'continue_writing' => 'You are a writing assistant who continues content naturally. Match the existing tone and style perfectly.',
                'suggest_outline' => 'You are a content strategist who creates comprehensive blog post outlines. Focus on logical flow and reader engagement.',
                'change_tone' => 'You are an expert at adapting writing tone while preserving the core message and information.',
                'expand_content' => 'You are a skilled writer who expands content with relevant details, examples, and insights.',
                'freeform' => 'You are a helpful writing assistant who provides thoughtful guidance on content creation.',
                'atomize_social' => "You are a social media expert who transforms blog content into engaging social media posts. You understand each platform's unique voice, format, and audience expectations. Create content that feels native to each platform while maintaining the core message.",
                'content_sprint' => 'You are an expert content strategist who generates creative, engaging blog post ideas. You understand what makes content valuable, shareable, and SEO-friendly. Generate diverse ideas that provide real value to readers while supporting business goals.',
                default => 'You are a helpful writing assistant.',
            };
        }

        // Add brand voice if available
        if ($brand->voice_settings) {
            $systemPrompt .= "\n\nBrand Voice Guidelines:";

            if (isset($brand->voice_settings['tone'])) {
                $systemPrompt .= "\nTone: {$brand->voice_settings['tone']}";
            }

            if (isset($brand->voice_settings['style'])) {
                $systemPrompt .= "\nStyle: {$brand->voice_settings['style']}";
            }

            if (! empty($brand->voice_settings['sample_texts'])) {
                $systemPrompt .= "\n\nExample of the brand's writing style:";
                foreach ($brand->voice_settings['sample_texts'] as $sample) {
                    $systemPrompt .= "\n---\n{$sample}\n---";
                }
            }
        }

        if ($brand->industry) {
            $systemPrompt .= "\n\nIndustry context: {$brand->industry}";
        }

        return $systemPrompt;
    }
}
