<?php

namespace App\Http\Controllers;

use App\Enums\FeedbackStatus;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use App\Models\User;
use App\Notifications\FeedbackReceivedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Feedback::query()
            ->where('user_id', auth()->id())
            ->with('brand')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $feedback = $query->paginate(20);

        return Inertia::render('Feedback/Index', [
            'feedback' => FeedbackResource::collection($feedback)->resolve(),
            'pagination' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
            ],
            'filters' => $request->only(['status']),
            'statuses' => collect(FeedbackStatus::cases())
                ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                ->toArray(),
        ]);
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['brand_id'] = auth()->user()->currentBrand()?->id;

        if ($request->hasFile('screenshot')) {
            $data['screenshot_path'] = $this->uploadScreenshot($request->file('screenshot'));
        }

        unset($data['screenshot']);

        $feedback = Feedback::create($data);

        $this->notifyAdmins($feedback);

        return redirect()->back()->with('success', "Thank you for your feedback! We'll review it shortly.");
    }

    protected function uploadScreenshot(\Illuminate\Http\UploadedFile $file): string
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = now()->format('Y/m').'/'.$filename;

        Storage::disk('s3')->put('feedback/'.$path, file_get_contents($file));

        return $path;
    }

    protected function notifyAdmins(Feedback $feedback): void
    {
        $adminEmails = config('admin.emails', []);

        if (empty($adminEmails)) {
            return;
        }

        $admins = User::query()->whereIn('email', $adminEmails)->get();

        foreach ($admins as $admin) {
            $admin->notify(new FeedbackReceivedNotification($feedback));
        }
    }
}
