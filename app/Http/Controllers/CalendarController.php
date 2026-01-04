<?php

namespace App\Http\Controllers;

use App\Enums\NewsletterSendStatus;
use App\Enums\PostStatus;
use App\Enums\SocialPostStatus;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Models\NewsletterSend;
use App\Models\Post;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    use HasBrandAuthorization;

    public function index(Request $request): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        // Mark calendar as viewed for onboarding
        if (! ($brand->onboarding_status['calendar_viewed'] ?? false)) {
            $status = $brand->onboarding_status ?? [];
            $status['calendar_viewed'] = true;
            $brand->update(['onboarding_status' => $status]);
        }

        $month = $request->get('month', now()->format('Y-m'));

        // Parse month into date range
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        // Fetch all content for the month
        $posts = Post::where('brand_id', $brand->id)
            ->where(function (Builder $q) use ($startOfMonth, $endOfMonth): void {
                $q->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('published_at', [$startOfMonth, $endOfMonth]);
            })
            ->whereIn('status', [PostStatus::Scheduled, PostStatus::Published])
            ->get();

        $socialPosts = SocialPost::where('brand_id', $brand->id)
            ->where(function (Builder $q) use ($startOfMonth, $endOfMonth): void {
                $q->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('published_at', [$startOfMonth, $endOfMonth]);
            })
            ->whereIn('status', [SocialPostStatus::Scheduled, SocialPostStatus::Published, SocialPostStatus::Failed])
            ->with('post:id,title')
            ->get();

        $newsletterSends = NewsletterSend::where('brand_id', $brand->id)
            ->where(function (Builder $q) use ($startOfMonth, $endOfMonth): void {
                $q->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
                    ->orWhereBetween('sent_at', [$startOfMonth, $endOfMonth]);
            })
            ->whereIn('status', [NewsletterSendStatus::Scheduled, NewsletterSendStatus::Sent])
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

    /**
     * @param  Collection<int, Post>  $posts
     * @param  Collection<int, SocialPost>  $socialPosts
     * @param  Collection<int, NewsletterSend>  $newsletterSends
     * @return array<int, array<string, mixed>>
     */
    private function transformToEvents(Collection $posts, Collection $socialPosts, Collection $newsletterSends): array
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
