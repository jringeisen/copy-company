<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackStatus;
use App\Enums\FeedbackType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\UpdateFeedbackStatusRequest;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use App\Notifications\FeedbackStatusUpdatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminFeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Feedback::query()
            ->with(['user', 'brand'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        if ($request->filled('priority')) {
            $query->ofPriority($request->input('priority'));
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $feedback = $query->paginate(15);

        $hasFilters = $request->hasAny(['status', 'type', 'priority', 'search']);

        $stats = [
            'total' => $hasFilters ? Feedback::query()->count() : $feedback->total(),
            'open' => Feedback::query()->where('status', FeedbackStatus::Open)->count(),
            'in_progress' => Feedback::query()->where('status', FeedbackStatus::InProgress)->count(),
            'resolved' => Feedback::query()->where('status', FeedbackStatus::Resolved)->count(),
        ];

        return Inertia::render('Admin/Feedback/Index', [
            'feedback' => FeedbackResource::collection($feedback)->resolve(),
            'pagination' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
            ],
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'priority', 'search']),
            'types' => FeedbackType::toDropdownOptions(),
            'priorities' => FeedbackPriority::toDropdownOptions(),
            'statuses' => collect(FeedbackStatus::cases())
                ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                ->toArray(),
        ]);
    }

    public function show(Feedback $feedback): Response
    {
        $feedback->load(['user', 'brand']);

        return Inertia::render('Admin/Feedback/Show', [
            'feedback' => (new FeedbackResource($feedback))->resolve(),
            'statuses' => collect(FeedbackStatus::cases())
                ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                ->toArray(),
        ]);
    }

    public function update(UpdateFeedbackStatusRequest $request, Feedback $feedback): RedirectResponse
    {
        $data = $request->validated();

        $previousStatus = $feedback->status;
        $newStatus = FeedbackStatus::from($data['status']);

        $feedback->update([
            'status' => $newStatus,
            'admin_notes' => $data['admin_notes'] ?? $feedback->admin_notes,
            'resolved_at' => $newStatus->isClosed() && ! $feedback->resolved_at ? now() : $feedback->resolved_at,
        ]);

        if ($previousStatus !== $newStatus) {
            $feedback->user->notify(new FeedbackStatusUpdatedNotification($feedback));
        }

        return redirect()->back()->with('success', 'Feedback updated successfully.');
    }
}
