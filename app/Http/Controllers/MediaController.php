<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\Media\BulkDeleteMediaRequest;
use App\Http\Requests\Media\MoveMediaRequest;
use App\Http\Requests\Media\StoreMediaRequest;
use App\Http\Requests\Media\UpdateMediaRequest;
use App\Http\Resources\MediaFolderResource;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaFolderService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    use HasBrandAuthorization;

    public function __construct(
        protected MediaService $mediaService,
        protected MediaFolderService $mediaFolderService
    ) {}

    public function index(): Response|RedirectResponse
    {
        $brand = $this->requireBrand();

        if ($this->isRedirect($brand)) {
            return $brand;
        }

        $folderId = request('folder_id');

        $media = $brand->media()
            ->when($folderId, fn (\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder => $q->where('folder_id', $folderId))
            ->when(! $folderId, fn (\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder => $q->whereNull('folder_id'))
            ->with('folder')
            ->orderByDesc('created_at')
            ->paginate(24);

        $folders = $this->mediaFolderService->getTree($brand);
        $currentFolder = $folderId ? $brand->mediaFolders()->find($folderId) : null;

        return Inertia::render('Media/Index', [
            'media' => MediaResource::collection($media),
            'folders' => MediaFolderResource::collection($folders)->resolve(),
            'currentFolder' => $currentFolder ? (new MediaFolderResource($currentFolder))->resolve() : null,
        ]);
    }

    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();
        $validated = $request->validated();

        $uploadedMedia = [];
        foreach ($request->file('images') as $file) {
            $uploadedMedia[] = $this->mediaService->upload(
                $file,
                $brand,
                auth()->id(),
                $validated['folder_id'] ?? null
            );
        }

        $count = count($uploadedMedia);

        return back()->with('success', "{$count} image(s) uploaded successfully.");
    }

    public function update(UpdateMediaRequest $request, Media $media): RedirectResponse
    {
        $this->authorize('update', $media);

        $validated = $request->validated();
        $this->mediaService->updateAltText($media, $validated['alt_text'] ?? null);

        return back()->with('success', 'Image updated.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        $this->mediaService->delete($media);

        return back()->with('success', 'Image deleted.');
    }

    public function bulkDestroy(BulkDeleteMediaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $brand = $this->currentBrand();

        $deleted = $this->mediaService->bulkDelete($validated['ids'], $brand);

        return back()->with('success', "{$deleted} image(s) deleted.");
    }

    public function move(MoveMediaRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $brand = $this->currentBrand();

        $media = Media::where('brand_id', $brand->id)
            ->whereIn('id', $validated['ids'])
            ->get();

        foreach ($media as $item) {
            $this->mediaService->move($item, $validated['folder_id']);
        }

        $count = $media->count();

        return back()->with('success', "{$count} image(s) moved.");
    }

    /**
     * API endpoint for fetching media (used by MediaPicker modal).
     */
    public function list(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
    {
        $brand = $this->currentBrand();

        if (! $brand) {
            return response()->json(['data' => []], 200);
        }

        $folderId = request('folder_id');
        $search = request('search');

        $media = $brand->media()
            ->when($folderId, fn (\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder => $q->where('folder_id', $folderId))
            ->when($search, fn (\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder => $q->where('filename', 'like', "%{$search}%"))
            ->with('folder')
            ->orderByDesc('created_at')
            ->paginate(24);

        return MediaResource::collection($media);
    }

    /**
     * Serve media file via redirect to signed URL (or regular URL for local disk).
     *
     * This provides a permanent URL that can be stored in content (e.g., TipTap)
     * while still using signed URLs for actual S3 access.
     */
    public function view(Media $media): RedirectResponse
    {
        $url = $this->getMediaUrl($media, $media->path);

        return redirect()->away($url);
    }

    /**
     * Serve thumbnail via redirect to signed URL (or regular URL for local disk).
     */
    public function thumbnail(Media $media): RedirectResponse
    {
        $path = $media->thumbnail_path ?? $media->path;
        $url = $this->getMediaUrl($media, $path);

        return redirect()->away($url);
    }

    /**
     * Get URL for media path, using temporaryUrl for S3 or url for local disks.
     */
    protected function getMediaUrl(Media $media, string $path): string
    {
        $disk = Storage::disk($media->disk);

        // Check if disk supports temporary URLs (S3, etc.)
        if ($media->disk !== 'local' && $media->disk !== 'public') {
            try {
                return $disk->temporaryUrl($path, now()->addHours(1));
            } catch (\RuntimeException $e) {
                // Fall back to regular URL if temporaryUrl is not supported
            }
        }

        return $disk->url($path);
    }
}
