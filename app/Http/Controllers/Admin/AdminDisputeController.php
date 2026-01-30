<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDisputeController extends Controller
{
    /**
     * Display a list of all disputes.
     */
    public function index(Request $request): Response
    {
        $query = Dispute::query()->with('account')->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by resolution
        if ($request->filled('resolution')) {
            $query->where('resolution', $request->input('resolution'));
        }

        $disputes = $query->paginate(15);

        $hasFilters = $request->filled('status') || $request->filled('resolution');

        $stats = [
            'total' => $hasFilters ? Dispute::query()->count() : $disputes->total(),
            'open' => Dispute::query()->open()->count(),
            'requiring_action' => Dispute::query()->requiringAction()->count(),
            'won' => Dispute::query()->where('resolution', 'won')->count(),
            'lost' => Dispute::query()->where('resolution', 'lost')->count(),
            'total_amount_disputed' => Dispute::query()->sum('amount'),
            'total_amount_lost' => Dispute::query()->where('resolution', 'lost')->sum('amount'),
        ];

        return Inertia::render('Admin/Disputes/Index', [
            'disputes' => DisputeResource::collection($disputes)->resolve(),
            'pagination' => [
                'current_page' => $disputes->currentPage(),
                'last_page' => $disputes->lastPage(),
                'per_page' => $disputes->perPage(),
                'total' => $disputes->total(),
            ],
            'stats' => $stats,
            'filters' => $request->only(['status', 'resolution']),
        ]);
    }

    /**
     * Display a single dispute.
     */
    public function show(Dispute $dispute): Response
    {
        $dispute->load(['account', 'events']);

        return Inertia::render('Admin/Disputes/Show', [
            'dispute' => (new DisputeResource($dispute))->resolve(),
            'account' => $dispute->account ? [
                'id' => $dispute->account->id,
                'name' => $dispute->account->name,
                'email' => $dispute->account->admins()->first()?->email,
                'created_at' => $dispute->account->created_at->format('M d, Y'),
            ] : null,
            'events' => $dispute->events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_at' => $event->event_at->format('M d, Y g:i A'),
                    'event_at_relative' => $event->event_at->diffForHumans(),
                ];
            }),
        ]);
    }
}
