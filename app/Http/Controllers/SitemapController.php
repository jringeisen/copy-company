<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $posts = Post::query()
            ->where('status', 'published')
            ->where('publish_to_blog', true)
            ->whereNotNull('published_at')
            ->with('brand:id,slug')
            ->select(['id', 'slug', 'brand_id', 'published_at', 'updated_at'])
            ->orderByDesc('published_at')
            ->get();

        $brands = Brand::query()
            ->whereHas('posts', function ($query): void {
                $query->where('status', 'published')
                    ->where('publish_to_blog', true)
                    ->whereNotNull('published_at');
            })
            ->select(['id', 'slug'])
            ->get();

        $appUrl = config('app.url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        $xml .= $this->buildUrl($appUrl, 'weekly', '1.0');
        $xml .= $this->buildUrl("{$appUrl}/privacy-policy", 'monthly', '0.3');
        $xml .= $this->buildUrl("{$appUrl}/terms-of-service", 'monthly', '0.3');

        foreach ($brands as $brand) {
            $xml .= $this->buildUrl("{$appUrl}/blog/{$brand->slug}", 'daily', '0.7');
        }

        foreach ($posts as $post) {
            $lastmod = ($post->updated_at ?? $post->published_at)->toW3cString();
            $xml .= $this->buildUrl(
                "{$appUrl}/blog/{$post->brand->slug}/{$post->slug}",
                'monthly',
                '0.8',
                $lastmod
            );
        }

        $xml .= '</urlset>';

        return response($xml)
            ->header('Content-Type', 'application/xml');
    }

    private function buildUrl(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string
    {
        $url = "    <url>\n";
        $url .= "        <loc>{$loc}</loc>\n";
        if ($lastmod) {
            $url .= "        <lastmod>{$lastmod}</lastmod>\n";
        }
        $url .= "        <changefreq>{$changefreq}</changefreq>\n";
        $url .= "        <priority>{$priority}</priority>\n";
        $url .= "    </url>\n";

        return $url;
    }
}
