<?php

namespace App\Http\Controllers;

use App\Http\Resources\NewsletterSendResource;
use App\Models\EmailEvent;
use App\Models\NewsletterSend;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    public function index(Request $request): Response
    {
        $newsletters = $request->user()->currentBrand()
            ->newsletterSends()
            ->with('post:id,title,slug')
            ->latest()
            ->paginate(15);

        return Inertia::render('Newsletters/Index', [
            'newsletters' => NewsletterSendResource::collection($newsletters)->resolve(),
            'pagination' => [
                'current_page' => $newsletters->currentPage(),
                'last_page' => $newsletters->lastPage(),
                'per_page' => $newsletters->perPage(),
                'total' => $newsletters->total(),
            ],
        ]);
    }

    public function show(Request $request, NewsletterSend $newsletterSend): Response
    {
        // Verify the newsletter belongs to the current brand
        if ($newsletterSend->brand_id !== $request->user()->currentBrand()->id) {
            abort(404);
        }

        $eventCounts = EmailEvent::where('newsletter_send_id', $newsletterSend->id)
            ->selectRaw('event_type, count(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return Inertia::render('Newsletters/Show', [
            'newsletter' => (new NewsletterSendResource($newsletterSend->load('post')))->resolve(),
            'eventCounts' => $eventCounts,
        ]);
    }
}
