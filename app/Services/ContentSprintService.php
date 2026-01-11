<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContentSprintService
{
    /**
     * Accept ideas from a content sprint and convert them to draft posts.
     *
     * @param  array<int>  $ideaIndices
     * @return Collection<int, Post>
     */
    public function acceptIdeas(ContentSprint $sprint, Brand $brand, int $userId, array $ideaIndices): Collection
    {
        $generatedContent = $sprint->generated_content ?? [];
        $posts = collect();

        foreach ($ideaIndices as $index) {
            if (! isset($generatedContent[$index])) {
                continue;
            }

            $idea = $generatedContent[$index];
            $post = $this->createPostFromIdea($brand, $userId, $idea);
            $posts->push($post);
        }

        $this->trackConvertedIdeas($sprint, $ideaIndices);

        return $posts;
    }

    /**
     * Create a draft post from a sprint idea.
     *
     * @param  array<string, mixed>  $idea
     */
    protected function createPostFromIdea(Brand $brand, int $userId, array $idea): Post
    {
        $content = $this->buildTipTapContent($idea);

        /** @var Post */
        return $brand->posts()->create([
            'user_id' => $userId,
            'title' => $idea['title'],
            'slug' => Str::slug($idea['title']),
            'excerpt' => $idea['description'] ?? null,
            'content' => $content,
            'status' => PostStatus::Draft,
        ]);
    }

    /**
     * Build TipTap JSON content with an AI-friendly prompt from the sprint idea.
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

        // Add description paragraph
        if ($description) {
            $content[] = [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Description: '],
                    ['type' => 'text', 'text' => $description],
                ],
            ];
        }

        // Add key points section
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

        // Add word count target
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
     * Track which ideas have been converted to posts.
     *
     * @param  array<int>  $newIndices
     */
    protected function trackConvertedIdeas(ContentSprint $sprint, array $newIndices): void
    {
        $existingConverted = $sprint->converted_indices ?? [];
        $allConverted = array_values(array_unique(array_merge($existingConverted, $newIndices)));
        $sprint->update(['converted_indices' => $allConverted]);
    }
}
