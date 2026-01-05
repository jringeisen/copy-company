<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasBrandAuthorization;
use App\Http\Requests\MediaFolder\StoreFolderRequest;
use App\Http\Requests\MediaFolder\UpdateFolderRequest;
use App\Http\Resources\MediaFolderResource;
use App\Models\MediaFolder;
use App\Services\MediaFolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class MediaFolderController extends Controller
{
    use HasBrandAuthorization;

    public function __construct(
        protected MediaFolderService $mediaFolderService
    ) {}

    /**
     * Get all folders as a tree structure.
     */
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
    {
        $brand = $this->currentBrand();

        if (! $brand) {
            return response()->json(['data' => []], 200);
        }

        $folders = $this->mediaFolderService->getTree($brand);

        return MediaFolderResource::collection($folders);
    }

    public function store(StoreFolderRequest $request): RedirectResponse
    {
        $brand = $this->currentBrand();
        $validated = $request->validated();

        $this->mediaFolderService->create(
            $brand,
            $validated['name'],
            $validated['parent_id'] ?? null
        );

        return back()->with('success', 'Folder created.');
    }

    public function update(UpdateFolderRequest $request, MediaFolder $folder): RedirectResponse
    {
        $this->authorize('update', $folder);

        $validated = $request->validated();
        $this->mediaFolderService->rename($folder, $validated['name']);

        return back()->with('success', 'Folder renamed.');
    }

    public function destroy(MediaFolder $folder): RedirectResponse
    {
        $this->authorize('delete', $folder);

        $this->mediaFolderService->delete($folder);

        return redirect()->route('media.index')->with('success', 'Folder deleted.');
    }
}
