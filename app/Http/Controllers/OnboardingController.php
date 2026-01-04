<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasBrandAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class OnboardingController extends Controller
{
    use HasBrandAuthorization;

    /**
     * Mark a specific onboarding step as complete.
     */
    public function markStepComplete(string $step): JsonResponse
    {
        $brand = $this->currentBrand();

        if (! $brand) {
            return response()->json(['error' => 'No brand found'], 404);
        }

        $validSteps = ['calendar_viewed'];

        if (! in_array($step, $validSteps)) {
            return response()->json(['error' => 'Invalid step'], 400);
        }

        $status = $brand->onboarding_status ?? [];
        $status[$step] = true;
        $brand->update(['onboarding_status' => $status]);

        return response()->json(['success' => true]);
    }

    /**
     * Dismiss the onboarding checklist.
     */
    public function dismiss(): RedirectResponse
    {
        $brand = $this->currentBrand();

        if ($brand) {
            $brand->update(['onboarding_dismissed' => true]);
        }

        return back();
    }
}
