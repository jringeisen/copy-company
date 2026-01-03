<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $brand = auth()->user()->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        $month = $request->get('month', now()->format('Y-m'));

        // Parse month into date range
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        // Fetch all content for the month
        $posts = Post::where('brand_id', $brand->id)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('published_at', [$startOfMonth, $endOfMonth]);
            })
            ->whereIn('status', ['scheduled', 'published'])
            ->get();

        $socialPosts = SocialPost::where('brand_id', $brand->id)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('published_at', [$startOfMonth, $endOfMonth]);
            })
            ->whereIn('status', ['scheduled', 'published'])
            ->with('post:id,title')
            ->get();

        $newsletterSends = NewsletterSend::where('brand_id', $brand->id)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('sent_at', [$startOfMonth, $endOfMonth]);
            })
            ->whereIn('status', ['scheduled', 'sent'])
            ->with('post:id,title')
            ->get();

        // Transform into calendar events
        $events = $this->transformToEvents($posts, $socialPosts, $newsletterSends);

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'currentMonth' => $month,
            'brand' => $brand,
        ]);
    }

    private function transformToEvents($posts, $socialPosts, $newsletterSends): array
    {
        $events = [];

        foreach ($posts as $post) {
            $date = $post->scheduled_at ?? $post->published_at;
            if ($date) {
                $events[] = [
                    'id' => 'post-'.$post->id,
                    'type' => 'post',
                    'title' => $post->title,
                    'date' => $date->format('Y-m-d'),
                    'time' => $date->format('g:i A'),
                    'status' => $post->status,
                    'url' => route('posts.edit', $post),
                ];
            }
        }

        foreach ($socialPosts as $social) {
            $date = $social->scheduled_at ?? $social->published_at;
            if ($date) {
                $events[] = [
                    'id' => 'social-'.$social->id,
                    'type' => 'social',
                    'platform' => $social->platform,
                    'title' => Str::limit($social->content, 50),
                    'date' => $date->format('Y-m-d'),
                    'time' => $date->format('g:i A'),
                    'status' => $social->status,
                    'post_title' => $social->post?->title,
                ];
            }
        }

        foreach ($newsletterSends as $newsletter) {
            $date = $newsletter->scheduled_at ?? $newsletter->sent_at;
            if ($date) {
                $events[] = [
                    'id' => 'newsletter-'.$newsletter->id,
                    'type' => 'newsletter',
                    'title' => $newsletter->subject_line,
                    'date' => $date->format('Y-m-d'),
                    'time' => $date->format('g:i A'),
                    'status' => $newsletter->status,
                    'recipients' => $newsletter->recipients_count,
                    'post_title' => $newsletter->post?->title,
                ];
            }
        }

        return $events;
    }
}
