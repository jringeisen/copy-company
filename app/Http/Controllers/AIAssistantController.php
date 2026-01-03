<?php

namespace App\Http\Controllers;

use App\Http\Requests\AI\AskRequest;
use App\Http\Requests\AI\AtomizeRequest;
use App\Http\Requests\AI\ChangeToneRequest;
use App\Http\Requests\AI\ContentRequest;
use App\Http\Requests\AI\DraftRequest;
use App\Http\Requests\AI\OutlineRequest;
use App\Models\Post;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;

class AIAssistantController extends Controller
{
    public function __construct(
        private readonly AIService $aiService
    ) {}

    public function draft(DraftRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();

        try {
            $result = $this->aiService->generateDraft(
                $brand,
                ...$request->validated()
            );

            return response()->json(['content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate content'], 500);
        }
    }

    public function polish(ContentRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();

        try {
            $result = $this->aiService->polishWriting($brand, $request->validated());

            return response()->json(['content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to polish content'], 500);
        }
    }

    public function continue(ContentRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();

        try {
            $result = $this->aiService->continueWriting($brand, $request->validated());

            return response()->json(['content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to continue content'], 500);
        }
    }

    public function outline(OutlineRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();
        $validated = $request->validated();

        try {
            $result = $this->aiService->suggestOutline(
                $brand,
                $validated['title'],
                $validated['notes'] ?? null
            );

            return response()->json(['content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate outline'], 500);
        }
    }

    public function changeTone(ChangeToneRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();

        try {
            $result = $this->aiService->changeTone($brand, ...$request->validated());

            return response()->json(['content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to change tone'], 500);
        }
    }

    public function shorter(ContentRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();

        try {
            $result = $this->aiService->makeItShorter($brand, $request->validated());

            return response()->json(['content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to shorten content'], 500);
        }
    }

    public function longer(ContentRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();

        try {
            $result = $this->aiService->makeItLonger($brand, $request->validated());

            return response()->json(['content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to expand content'], 500);
        }
    }

    public function ask(AskRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();

        try {
            $result = $this->aiService->freeformQuestion(
                $brand,
                ...$request->validated(),
            );

            return response()->json(['content' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process question'], 500);
        }
    }

    public function atomize(AtomizeRequest $request): JsonResponse
    {
        $brand = auth()->user()->currentBrand();
        $validated = $request->validated();

        $post = Post::findOrFail($validated['post_id']);

        try {
            $result = $this->aiService->atomizeToSocial(
                $brand,
                $post,
                $validated['platforms']
            );

            return response()->json(['posts' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate social posts: '.$e->getMessage()], 500);
        }
    }
}
