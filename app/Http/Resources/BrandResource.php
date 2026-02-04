<?php

namespace App\Http\Resources;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Brand
 */
class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'tagline' => $this->tagline,
            'description' => $this->description,
            'industry' => $this->industry,
            'timezone' => $this->timezone,
            'logo_path' => $this->logo_path,
            'favicon_path' => $this->favicon_path,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'custom_domain' => $this->custom_domain,
            'domain_verified' => $this->domain_verified,
            'newsletter_provider' => $this->newsletter_provider->value ?? null,
            'voice_settings' => $this->voice_settings,
            'strategy_context' => $this->strategy_context,
            'url' => $this->url,
            'active_subscribers_count' => $this->when(
                $this->relationLoaded('subscribers'),
                fn () => $this->active_subscribers_count
            ),
            'created_at' => $this->created_at?->format('M d, Y g:i A'),
            'updated_at' => $this->updated_at?->format('M d, Y g:i A'),
        ];
    }
}
