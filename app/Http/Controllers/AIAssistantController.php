<?php

namespace App\Http\Controllers;

use App\Http\Requests\AI\AskRequest;
use App\Http\Requests\AI\AtomizeRequest;
use App\Http\Requests\AI\ChangeToneRequest;
use App\Http\Requests\AI\ContentRequest;
use App\Http\Requests\AI\DraftRequest;
use App\Http\Requests\AI\OutlineRequest;
use App\Models\Brand;
use App\Models\Post;
use App\Services\AIService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AIAssistantController extends Controller
{
    public function __construct(
        private readonly AIService $aiService
    ) {}

    public function draft(DraftRequest $request): JsonResponse
    {
        return $this->handleAIRequest('generate draft', function (Brand $brand) use ($request) {
            return $this->aiService->generateDraft(
                $brand,
                ...$request->validated()
            );
        });
    }

    public function polish(ContentRequest $request): JsonResponse
    {
        return $this->handleAIRequest('polish content', function (Brand $brand) use ($request) {
            return $this->aiService->polishWriting($brand, $request->validated()['content']);
        });
    }

    public function continue(ContentRequest $request): JsonResponse
    {
        return $this->handleAIRequest('continue writing', function (Brand $brand) use ($request) {
            return $this->aiService->continueWriting($brand, $request->validated()['content']);
        });
    }

    public function outline(OutlineRequest $request): JsonResponse
    {
        return $this->handleAIRequest('generate outline', function (Brand $brand) use ($request) {
            $validated = $request->validated();

            return $this->aiService->suggestOutline(
                $brand,
                $validated['title'],
                $validated['notes'] ?? null
            );
        });
    }

    public function changeTone(ChangeToneRequest $request): JsonResponse
    {
        return $this->handleAIRequest('change tone', function (Brand $brand) use ($request) {
            $validated = $request->validated();

            return $this->aiService->changeTone($brand, $validated['content'], $validated['tone']);
        });
    }

    public function shorter(ContentRequest $request): JsonResponse
    {
        return $this->handleAIRequest('shorten content', function (Brand $brand) use ($request) {
            return $this->aiService->makeItShorter($brand, $request->validated()['content']);
        });
    }

    public function longer(ContentRequest $request): JsonResponse
    {
        return $this->handleAIRequest('expand content', function (Brand $brand) use ($request) {
            return $this->aiService->makeItLonger($brand, $request->validated()['content']);
        });
    }

    public function ask(AskRequest $request): JsonResponse
    {
        return $this->handleAIRequest('process question', function (Brand $brand) use ($request) {
            return $this->aiService->freeformQuestion(
                $brand,
                ...$request->validated(),
            );
        });
    }

    public function atomize(AtomizeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $post = Post::findOrFail($validated['post_id']);

        return $this->handleAIRequest('generate social posts', function (Brand $brand) use ($validated, $post) {
            return $this->aiService->atomizeToSocial(
                $brand,
                $post,
                $validated['platforms']
            );
        }, responseKey: 'posts');
    }

    /**
     * Handle AI request with consistent error handling and logging.
     *
     * @param  callable(Brand): mixed  $callback
     */
    private function handleAIRequest(string $operation, callable $callback, string $responseKey = 'content'): JsonResponse
    {
        $brand = auth()->user()->currentBrand();

        if (! $brand) {
            return response()->json(['error' => 'No brand configured'], 400);
        }

        try {
            $result = $callback($brand);

            return response()->json([$responseKey => $result]);
        } catch (ConnectionException $e) {
            Log::warning("AI connection error during {$operation}", [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Unable to connect to AI service. Please try again.',
            ], 503);
        } catch (\Exception $e) {
            Log::error("AI error during {$operation}", [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = $this->getErrorMessage($e, $operation);

            return response()->json(['error' => $errorMessage], 500);
        }
    }

    /**
     * Get a user-friendly error message based on the exception.
     */
    private function getErrorMessage(\Exception $e, string $operation): string
    {
        $message = $e->getMessage();

        // Check for common API error patterns
        if (str_contains($message, 'rate limit') || str_contains($message, '429')) {
            return 'AI service rate limit reached. Please wait a moment and try again.';
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'The AI service took too long to respond. Please try again.';
        }

        if (str_contains($message, 'authentication') || str_contains($message, '401')) {
            return 'AI service authentication failed. Please contact support.';
        }

        if (str_contains($message, 'quota') || str_contains($message, 'insufficient')) {
            return 'AI service quota exceeded. Please contact support.';
        }

        // In development, show the actual error message
        if (app()->environment('local')) {
            return "Failed to {$operation}: {$message}";
        }

        return "Failed to {$operation}. Please try again.";
    }
}
