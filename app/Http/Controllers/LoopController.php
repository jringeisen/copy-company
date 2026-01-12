<?php

namespace App\Http\Controllers;

use App\Enums\DayOfWeek;
use App\Enums\SocialPlatform;
use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\Loop\ImportLoopItemsRequest;
use App\Http\Requests\Loop\ReorderLoopItemsRequest;
use App\Http\Requests\Loop\StoreLoopItemRequest;
use App\Http\Requests\Loop\StoreLoopRequest;
use App\Http\Requests\Loop\UpdateLoopItemRequest;
use App\Http\Requests\Loop\UpdateLoopRequest;
use App\Http\Resources\LoopResource;
use App\Http\Resources\SocialPostResource;
use App\Models\Loop;
use App\Models\LoopItem;
use App\Models\SocialPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LoopController extends Controller
{
    use HasBrandAuthorization;

    public function index(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $loops = $brand->loops()
            ->withCount('items')
            ->orderByDesc('updated_at')
            ->get();

        // Set brand relationship to avoid N+1 queries in LoopResource
        $loops->each(fn (Loop $loop) => $loop->setRelation('brand', $brand));

        return Inertia::render('Loops/Index', [
            'loops' => LoopResource::collection($loops)->resolve(),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        return Inertia::render('Loops/Create', [
            'platforms' => SocialPlatform::toDropdownOptions(),
            'daysOfWeek' => DayOfWeek::toDropdownOptions(),
        ]);
    }

    public function store(StoreLoopRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();
        $validated = $request->validated();

        $loop = DB::transaction(function () use ($brand, $validated) {
            $loop = $brand->loops()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'platforms' => $validated['platforms'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Create schedules
            if (! empty($validated['schedules'])) {
                foreach ($validated['schedules'] as $schedule) {
                    $loop->schedules()->create([
                        'day_of_week' => $schedule['day_of_week'],
                        'time_of_day' => $schedule['time_of_day'],
                        'platform' => $schedule['platform'] ?? null,
                    ]);
                }
            }

            return $loop;
        });

        return redirect()->route('loops.show', $loop)
            ->with('success', 'Loop created successfully!');
    }

    public function show(Loop $loop): Response
    {
        $this->authorize('view', $loop);

        $brand = $this->currentBrand();

        $loop->load(['items.socialPost', 'schedules', 'brand']);

        // Set the loop relationship on each item to avoid N+1 queries in LoopItemResource
        $loop->items->each(fn (LoopItem $item) => $item->setRelation('loop', $loop));

        // Get available social posts for adding to loop
        $availableSocialPosts = $brand->socialPosts()
            ->whereNotIn('id', $loop->items()->whereNotNull('social_post_id')->pluck('social_post_id'))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Set brand relationship to avoid N+1 queries in SocialPostResource
        $availableSocialPosts->each(fn (SocialPost $post) => $post->setRelation('brand', $brand));

        return Inertia::render('Loops/Show', [
            'loop' => (new LoopResource($loop))->resolve(),
            'availableSocialPosts' => SocialPostResource::collection($availableSocialPosts)->resolve(),
            'platforms' => SocialPlatform::toDropdownOptions(),
            'daysOfWeek' => DayOfWeek::toDropdownOptions(),
        ]);
    }

    public function edit(Loop $loop): Response
    {
        $this->authorize('update', $loop);

        $loop->load(['schedules', 'brand']);

        return Inertia::render('Loops/Edit', [
            'loop' => (new LoopResource($loop))->resolve(),
            'platforms' => SocialPlatform::toDropdownOptions(),
            'daysOfWeek' => DayOfWeek::toDropdownOptions(),
        ]);
    }

    public function update(UpdateLoopRequest $request, Loop $loop): RedirectResponse
    {
        $this->authorize('update', $loop);

        $validated = $request->validated();

        DB::transaction(function () use ($loop, $validated) {
            $loop->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'platforms' => $validated['platforms'],
                'is_active' => $validated['is_active'] ?? $loop->is_active,
            ]);

            // Update schedules
            if (isset($validated['schedules'])) {
                $loop->schedules()->delete();
                foreach ($validated['schedules'] as $schedule) {
                    $loop->schedules()->create([
                        'day_of_week' => $schedule['day_of_week'],
                        'time_of_day' => $schedule['time_of_day'],
                        'platform' => $schedule['platform'] ?? null,
                    ]);
                }
            }
        });

        return back()->with('success', 'Loop updated successfully!');
    }

    public function destroy(Loop $loop): RedirectResponse
    {
        $this->authorize('delete', $loop);

        $loop->delete();

        return redirect()->route('loops.index')
            ->with('success', 'Loop deleted successfully.');
    }

    public function addItem(StoreLoopItemRequest $request, Loop $loop): RedirectResponse
    {
        $this->authorize('update', $loop);

        $validated = $request->validated();

        $maxPosition = $loop->items()->max('position') ?? -1;

        $loop->items()->create([
            'social_post_id' => $validated['social_post_id'] ?? null,
            'position' => $maxPosition + 1,
            'content' => $validated['content'] ?? null,
            'format' => $validated['format'] ?? 'feed',
            'hashtags' => $validated['hashtags'] ?? [],
            'link' => $validated['link'] ?? null,
            'media' => $validated['media'] ?? [],
        ]);

        return back()->with('success', 'Item added to loop!');
    }

    public function updateItem(UpdateLoopItemRequest $request, Loop $loop, LoopItem $item): RedirectResponse
    {
        $this->authorize('update', $loop);

        if ($item->loop_id !== $loop->id) {
            abort(404);
        }

        // Don't allow editing linked items - they should be edited via the social post
        if ($item->isLinked()) {
            return back()->with('error', 'Linked items cannot be edited directly. Edit the original social post instead.');
        }

        $validated = $request->validated();

        $item->update([
            'content' => $validated['content'],
            'format' => $validated['format'] ?? $item->format,
            'hashtags' => $validated['hashtags'] ?? [],
            'link' => $validated['link'] ?? null,
            'media' => $validated['media'] ?? [],
        ]);

        return back()->with('success', 'Item updated successfully!');
    }

    public function removeItem(Loop $loop, LoopItem $item): RedirectResponse
    {
        $this->authorize('update', $loop);

        if ($item->loop_id !== $loop->id) {
            abort(404);
        }

        $removedPosition = $item->position;
        $item->delete();

        // Reorder remaining items
        $loop->items()
            ->where('position', '>', $removedPosition)
            ->decrement('position');

        // Adjust current_position if needed
        if ($loop->current_position > $removedPosition) {
            $loop->decrement('current_position');
        } elseif ($loop->current_position >= $loop->items()->count()) {
            $loop->update(['current_position' => 0]);
        }

        return back()->with('success', 'Item removed from loop.');
    }

    public function reorder(ReorderLoopItemsRequest $request, Loop $loop): RedirectResponse
    {
        $this->authorize('update', $loop);

        $validated = $request->validated();

        DB::transaction(function () use ($loop, $validated) {
            foreach ($validated['items'] as $index => $itemId) {
                $loop->items()->where('id', $itemId)->update(['position' => $index]);
            }
        });

        return back()->with('success', 'Loop order updated!');
    }

    public function import(ImportLoopItemsRequest $request, Loop $loop): RedirectResponse
    {
        $this->authorize('update', $loop);

        $file = $request->file('file');
        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($loop, $file, &$imported, &$skipped) {
            $maxPosition = $loop->items()->max('position') ?? -1;

            $handle = fopen($file->getPathname(), 'r');

            // Skip header row
            fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $content = trim($row[0] ?? '');

                if (empty($content)) {
                    $skipped++;

                    continue;
                }

                // Sanitize content (CSV injection prevention)
                $content = preg_replace('/^[\=\+\-\@\t\r]/', "'", $content);

                $format = strtolower(trim($row[1] ?? 'feed'));
                $hashtags = ! empty($row[2]) ? array_map('trim', explode(',', $row[2])) : [];
                $link = trim($row[3] ?? '') ?: null;
                $mediaUrl = trim($row[4] ?? '') ?: null;

                // Validate format
                $validFormats = ['feed', 'story', 'reel', 'carousel', 'pin', 'thread'];
                if (! in_array($format, $validFormats)) {
                    $format = 'feed';
                }

                $maxPosition++;

                $loop->items()->create([
                    'position' => $maxPosition,
                    'content' => $content,
                    'format' => $format,
                    'hashtags' => $hashtags,
                    'link' => $link,
                    'media' => $mediaUrl ? [$mediaUrl] : [],
                ]);

                $imported++;
            }

            fclose($handle);
        });

        return back()->with('success', "Imported {$imported} items. Skipped {$skipped} invalid entries.");
    }

    public function toggle(Loop $loop): RedirectResponse
    {
        $this->authorize('update', $loop);

        $loop->update(['is_active' => ! $loop->is_active]);

        $status = $loop->is_active ? 'activated' : 'paused';

        return back()->with('success', "Loop {$status}!");
    }
}
