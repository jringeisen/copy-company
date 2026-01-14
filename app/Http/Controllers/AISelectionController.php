<?php

namespace App\Http\Controllers;

use App\Http\Requests\AI\SelectionRequest;
use App\Services\AI\SelectionEditor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AISelectionController extends Controller
{
    public function __construct(
        private readonly SelectionEditor $selectionEditor
    ) {}

    public function fixGrammar(SelectionRequest $request): JsonResponse
    {
        return $this->handleRequest('fix grammar', function () use ($request) {
            return $this->selectionEditor->fixGrammar($request->validated()['text']);
        });
    }

    public function simplify(SelectionRequest $request): JsonResponse
    {
        return $this->handleRequest('simplify text', function () use ($request) {
            return $this->selectionEditor->simplify($request->validated()['text']);
        });
    }

    public function rephrase(SelectionRequest $request): JsonResponse
    {
        return $this->handleRequest('rephrase text', function () use ($request) {
            return $this->selectionEditor->rephrase($request->validated()['text']);
        });
    }

    public function toList(SelectionRequest $request): JsonResponse
    {
        return $this->handleRequest('convert to list', function () use ($request) {
            return $this->selectionEditor->toList($request->validated()['text']);
        });
    }

    public function addExamples(SelectionRequest $request): JsonResponse
    {
        return $this->handleRequest('add examples', function () use ($request) {
            return $this->selectionEditor->addExamples($request->validated()['text']);
        });
    }

    /**
     * Handle AI request with consistent error handling.
     *
     * @param  callable(): string  $callback
     */
    private function handleRequest(string $operation, callable $callback): JsonResponse
    {
        try {
            $result = $callback();

            return response()->json(['content' => $result]);
        } catch (ConnectionException $e) {
            Log::warning("AI connection error during {$operation}", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Unable to connect to AI service. Please try again.',
            ], 503);
        } catch (\Exception $e) {
            Log::error("AI error during {$operation}", [
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

        if (str_contains($message, 'rate limit') || str_contains($message, '429')) {
            return 'AI service rate limit reached. Please wait a moment and try again.';
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'The AI service took too long to respond. Please try again.';
        }

        if (str_contains($message, 'authentication') || str_contains($message, '401')) {
            return 'AI service authentication failed. Please contact support.';
        }

        if (app()->environment('local')) {
            return "Failed to {$operation}: {$message}";
        }

        return "Failed to {$operation}. Please try again.";
    }
}
