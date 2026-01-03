<?php

namespace App\Http\Controllers;

use App\Enums\SubscriberStatus;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\Subscriber\ImportSubscribersRequest;
use App\Http\Resources\SubscriberResource;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller
{
    use HasBrandAuthorization;

    public function index(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $subscribers = $brand->subscribers()
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return Inertia::render('Subscribers/Index', [
            'subscribers' => SubscriberResource::collection($subscribers),
            'stats' => [
                'total' => $brand->subscribers()->count(),
                'confirmed' => $brand->subscribers()->where('status', SubscriberStatus::Confirmed)->count(),
                'unsubscribed' => $brand->subscribers()->where('status', SubscriberStatus::Unsubscribed)->count(),
            ],
        ]);
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $this->authorize('delete', $subscriber);

        $subscriber->delete();

        return back()->with('success', 'Subscriber removed successfully.');
    }

    public function export(): StreamedResponse|RedirectResponse
    {
        $brand = $this->currentBrand();

        if (! $brand) {
            return back()->with('error', 'No brand found.');
        }

        $subscribers = $brand->subscribers()
            ->where('status', SubscriberStatus::Confirmed)
            ->get(['email', 'name', 'created_at']);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subscribers.csv"',
        ];

        $callback = function () use ($subscribers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Email', 'Name', 'Subscribed At']);

            foreach ($subscribers as $subscriber) {
                fputcsv($file, [
                    $subscriber->email,
                    $subscriber->name,
                    $subscriber->created_at->toDateTimeString(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(ImportSubscribersRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();

        $file = $request->file('file');
        $handle = fopen($file->getPathname(), 'r');

        // Skip header row
        $header = fgetcsv($handle);

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $email = $row[0] ?? null;
            $name = $row[1] ?? null;

            if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;

                continue;
            }

            $existing = Subscriber::where('brand_id', $brand->id)
                ->where('email', $email)
                ->first();

            if ($existing) {
                $skipped++;

                continue;
            }

            Subscriber::create([
                'brand_id' => $brand->id,
                'email' => $email,
                'name' => $name,
                'status' => SubscriberStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $imported++;
        }

        fclose($handle);

        return back()->with('success', "Imported {$imported} subscribers. Skipped {$skipped} duplicates or invalid entries.");
    }
}
