<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    /**
     * Show the API tokens management page.
     */
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $tokens = $user->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at?->toIso8601String(),
                'last_used_at' => $token->last_used_at?->toIso8601String(),
            ]);

        return Inertia::render('Settings/ApiTokens', [
            'tokens' => $tokens,
        ]);
    }

    /**
     * Create a new API token.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Please provide a name for the token.',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $token = $user->createToken($validated['name']);

        return back()->with('newToken', $token->plainTextToken);
    }

    /**
     * Delete an API token.
     */
    public function destroy(PersonalAccessToken $token): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($token->tokenable_id !== $user->id) {
            abort(403, 'You do not have permission to delete this token.');
        }

        $token->delete();

        return back()->with('success', 'Token deleted successfully.');
    }
}
