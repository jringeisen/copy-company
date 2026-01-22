<?php

namespace App\Mcp\Resources;

use App\Models\Brand;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class BrandResource extends Resource
{
    protected string $name = 'brand';

    protected string $uri = 'brand://current';

    protected string $description = 'Information about the currently selected brand including settings, stats, and voice configuration.';

    public function handle(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return Response::error('Authentication required.');
        }

        /** @var Brand|null $brand */
        $brand = $user->currentBrand();

        if (! $brand) {
            return Response::error('No brand selected. Please set your current brand first.');
        }

        $voiceSettings = $brand->voice_settings ?? [];

        return Response::text(json_encode([
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'tagline' => $brand->tagline,
            'description' => $brand->description,
            'industry' => $brand->industry,
            'timezone' => $brand->timezone,
            'url' => $brand->url,
            'voice_settings' => [
                'tone' => $voiceSettings['tone'] ?? null,
                'style' => $voiceSettings['style'] ?? null,
                'has_sample_texts' => ! empty($voiceSettings['sample_texts']),
            ],
            'stats' => [
                'posts_count' => $brand->posts()->count(),
                'drafts_count' => $brand->posts()->draft()->count(),
                'published_count' => $brand->posts()->published()->count(),
                'scheduled_count' => $brand->posts()->scheduled()->count(),
                'subscribers_count' => $brand->active_subscribers_count,
                'sprints_count' => $brand->contentSprints()->count(),
            ],
            'features' => [
                'has_social_connections' => ! empty($brand->social_connections),
                'has_verified_email_domain' => $brand->hasVerifiedEmailDomain(),
                'newsletter_provider' => $brand->newsletter_provider?->value,
            ],
        ], JSON_PRETTY_PRINT));
    }
}
