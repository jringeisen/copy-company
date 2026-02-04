<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Models\Brand;
use App\Models\Loop;
use App\Models\MarketingStrategy;
use App\Models\Post;
use App\Models\SocialPost;
use Illuminate\Support\Str;

class MarketingStrategyService
{
    /**
     * Convert a blog post recommendation to a draft Post.
     */
    public function convertBlogPost(MarketingStrategy $strategy, Brand $brand, int $userId, int $index): Post
    {
        $blogPosts = $strategy->strategy_content['blog_posts'] ?? [];

        if (! isset($blogPosts[$index])) {
            throw new \InvalidArgumentException('Invalid blog post index.');
        }

        $idea = $blogPosts[$index];
        $content = $this->buildTipTapContent($idea);

        /** @var Post */
        $post = $brand->posts()->create([
            'user_id' => $userId,
            'title' => $idea['title'],
            'slug' => Str::slug($idea['title']),
            'excerpt' => $idea['description'] ?? null,
            'content' => $content,
            'status' => PostStatus::Draft,
        ]);

        $this->trackConvertedItem($strategy, 'blog_posts', $index);

        return $post;
    }

    /**
     * Convert a social post recommendation to a draft SocialPost.
     */
    public function convertSocialPost(MarketingStrategy $strategy, Brand $brand, int $index): SocialPost
    {
        $socialPosts = $strategy->strategy_content['social_posts'] ?? [];

        if (! isset($socialPosts[$index])) {
            throw new \InvalidArgumentException('Invalid social post index.');
        }

        $idea = $socialPosts[$index];
        $platform = SocialPlatform::tryFrom($idea['platform'] ?? 'instagram') ?? SocialPlatform::Instagram;

        /** @var SocialPost */
        $socialPost = $brand->socialPosts()->create([
            'platform' => $platform,
            'content' => $idea['content'],
            'hashtags' => $idea['hashtags'] ?? [],
            'status' => SocialPostStatus::Draft,
            'ai_generated' => true,
        ]);

        $this->trackConvertedItem($strategy, 'social_posts', $index);

        return $socialPost;
    }

    /**
     * Convert a newsletter recommendation to a draft Post with newsletter flag.
     */
    public function convertNewsletter(MarketingStrategy $strategy, Brand $brand, int $userId): Post
    {
        $newsletter = $strategy->strategy_content['newsletter'] ?? null;

        if (! $newsletter) {
            throw new \InvalidArgumentException('No newsletter recommendation found.');
        }

        $content = $this->buildNewsletterTipTapContent($newsletter);

        /** @var Post */
        $post = $brand->posts()->create([
            'user_id' => $userId,
            'title' => $newsletter['subject_line'] ?? 'Newsletter Draft',
            'slug' => Str::slug($newsletter['subject_line'] ?? 'newsletter-draft'),
            'excerpt' => $newsletter['topic'] ?? null,
            'content' => $content,
            'status' => PostStatus::Draft,
            'send_as_newsletter' => true,
        ]);

        $this->trackConvertedItem($strategy, 'newsletter', true);

        return $post;
    }

    /**
     * Convert a loop recommendation to a new Loop with items.
     */
    public function convertLoop(MarketingStrategy $strategy, Brand $brand, int $index): Loop
    {
        $loops = $strategy->strategy_content['loops'] ?? [];

        if (! isset($loops[$index])) {
            throw new \InvalidArgumentException('Invalid loop index.');
        }

        $loopData = $loops[$index];

        /** @var Loop */
        $loop = $brand->loops()->create([
            'name' => $loopData['name'],
            'description' => $loopData['description'] ?? '',
            'platforms' => $loopData['platforms'] ?? [],
            'is_active' => false,
        ]);

        $suggestedItems = $loopData['suggested_items'] ?? [];
        foreach ($suggestedItems as $position => $item) {
            $loop->items()->create([
                'content' => $item['content'],
                'hashtags' => $item['hashtags'] ?? [],
                'position' => $position,
            ]);
        }

        $this->trackConvertedItem($strategy, 'loops', $index);

        return $loop;
    }

    /**
     * Track a converted item in the strategy's converted_items JSON.
     */
    public function trackConvertedItem(MarketingStrategy $strategy, string $type, int|bool $index): void
    {
        $converted = $strategy->converted_items ?? [];

        if ($type === 'newsletter') {
            $converted['newsletter'] = true;
        } else {
            $existing = $converted[$type] ?? [];
            if (! in_array($index, $existing, true)) {
                $existing[] = $index;
            }
            $converted[$type] = $existing;
        }

        $strategy->update(['converted_items' => $converted]);
    }

    /**
     * Build TipTap JSON content from a blog post idea.
     *
     * @param  array<string, mixed>  $idea
     * @return array<string, mixed>
     */
    protected function buildTipTapContent(array $idea): array
    {
        $description = $idea['description'] ?? '';
        $keyPoints = $idea['key_points'] ?? [];
        $wordCount = $idea['estimated_words'] ?? 1000;

        $content = [];

        if ($description) {
            $content[] = [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Description: '],
                    ['type' => 'text', 'text' => $description],
                ],
            ];
        }

        if (! empty($keyPoints)) {
            $content[] = [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Key points to cover:'],
                ],
            ];

            $listItems = array_map(fn (string $point): array => [
                'type' => 'listItem',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => $point],
                        ],
                    ],
                ],
            ], $keyPoints);

            $content[] = [
                'type' => 'bulletList',
                'content' => $listItems,
            ];
        }

        $content[] = [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Target word count: '],
                ['type' => 'text', 'text' => (string) $wordCount.' words'],
            ],
        ];

        return [
            'type' => 'doc',
            'content' => $content,
        ];
    }

    /**
     * Build TipTap JSON content from a newsletter recommendation.
     *
     * @param  array<string, mixed>  $newsletter
     * @return array<string, mixed>
     */
    protected function buildNewsletterTipTapContent(array $newsletter): array
    {
        $content = [];

        if (! empty($newsletter['topic'])) {
            $content[] = [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Topic: '],
                    ['type' => 'text', 'text' => $newsletter['topic']],
                ],
            ];
        }

        $keyPoints = $newsletter['key_points'] ?? [];
        if (! empty($keyPoints)) {
            $content[] = [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Key points:'],
                ],
            ];

            $listItems = array_map(fn (string $point): array => [
                'type' => 'listItem',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => $point],
                        ],
                    ],
                ],
            ], $keyPoints);

            $content[] = [
                'type' => 'bulletList',
                'content' => $listItems,
            ];
        }

        return [
            'type' => 'doc',
            'content' => $content ?: [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Newsletter draft content...']]],
            ],
        ];
    }
}
