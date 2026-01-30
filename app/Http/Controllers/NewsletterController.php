<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Resources\NewsletterSendResource;
use App\Models\EmailEvent;
use App\Models\NewsletterSend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    use HasBrandAuthorization;

    public function index(Request $request): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        return Inertia::render('Newsletters/Index', [
            'newsletters' => Inertia::scroll(fn () => NewsletterSendResource::collection(
                $brand->newsletterSends()
                    ->with('post:id,title,slug')
                    ->latest()
                    ->paginate(15)
            )),
        ]);
    }

    public function show(Request $request, NewsletterSend $newsletterSend): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        // Verify the newsletter belongs to the current brand
        if ($newsletterSend->brand_id !== $brand->id) {
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
