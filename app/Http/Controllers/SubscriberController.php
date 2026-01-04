<?php

namespace App\Http\Controllers;

use App\Enums\SubscriberStatus;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\Subscriber\ImportSubscribersRequest;
use App\Http\Resources\SubscriberResource;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        // Optimized: single query for all stats instead of 3 separate queries
        $stats = $brand->subscribers()
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed', [SubscriberStatus::Confirmed->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as unsubscribed', [SubscriberStatus::Unsubscribed->value])
            ->first();

        return Inertia::render('Subscribers/Index', [
            'subscribers' => SubscriberResource::collection($subscribers),
            'stats' => [
                'total' => (int) $stats->total,
                'confirmed' => (int) $stats->confirmed,
                'unsubscribed' => (int) $stats->unsubscribed,
            ],
            'brand' => $brand,
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

        $this->authorize('update', $brand);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="subscribers.csv"',
        ];

        // Use cursor() to stream results without loading all into memory
        $callback = function () use ($brand) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Email', 'Name', 'Subscribed At']);

            $brand->subscribers()
                ->where('status', SubscriberStatus::Confirmed)
                ->select(['email', 'name', 'created_at'])
                ->cursor()
                ->each(function ($subscriber) use ($file) {
                    // Sanitize values to prevent CSV formula injection
                    $safeName = $subscriber->name ? preg_replace('/^[\=\+\-\@\t\r]/', "'", $subscriber->name) : '';
                    fputcsv($file, [
                        $subscriber->email,
                        $safeName,
                        $subscriber->created_at->toDateTimeString(),
                    ]);
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(ImportSubscribersRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();

        $file = $request->file('file');

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($brand, $file, &$imported, &$skipped) {
            // Pre-load existing emails for this brand to avoid N+1 queries
            $existingEmails = $brand->subscribers()
                ->pluck('email')
                ->map(fn ($email) => strtolower($email))
                ->flip()
                ->all();

            $handle = fopen($file->getPathname(), 'r');

            // Skip header row
            fgetcsv($handle);

            $batch = [];
            $batchSize = 500;
            $now = now();

            while (($row = fgetcsv($handle)) !== false) {
                $email = strtolower(trim($row[0] ?? ''));
                $name = $row[1] ?? null;

                // Sanitize name to prevent CSV formula injection on future exports
                if ($name) {
                    $name = preg_replace('/^[\=\+\-\@\t\r]/', "'", $name);
                }

                if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;

                    continue;
                }

                if (isset($existingEmails[$email])) {
                    $skipped++;

                    continue;
                }

                $batch[] = [
                    'brand_id' => $brand->id,
                    'email' => $email,
                    'name' => $name,
                    'status' => SubscriberStatus::Confirmed->value,
                    'confirmed_at' => $now,
                    'unsubscribe_token' => Str::random(64),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Mark as existing to prevent duplicates within the same import
                $existingEmails[$email] = true;
                $imported++;

                if (count($batch) >= $batchSize) {
                    Subscriber::insert($batch);
                    $batch = [];
                }
            }

            // Insert remaining records
            if (! empty($batch)) {
                Subscriber::insert($batch);
            }

            fclose($handle);
        });

        return back()->with('success', "Imported {$imported} subscribers. Skipped {$skipped} duplicates or invalid entries.");
    }
}
