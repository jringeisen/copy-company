<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComingSoonMode
{
    /**
     * Routes that are allowed even in coming soon mode.
     */
    protected array $allowedRoutes = [
        'coming-soon',
    ];

    /**
     * Redirect all visitors to the coming soon page in production.
     *
     * This middleware only activates when:
     * - APP_ENV is 'production'
     * - COMING_SOON_MODE is true (defaults to true in production)
     *
     * To bypass in production, set COMING_SOON_MODE=false in .env
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only activate in production when coming soon mode is enabled
        if (! $this->isComingSoonModeActive()) {
            return $next($request);
        }

        // Allow access to permitted routes
        if ($this->isAllowedRoute($request)) {
            return $next($request);
        }

        // Allow bypass with secret token (for owner testing)
        if ($this->hasBypassToken($request)) {
            return $next($request);
        }

        // Redirect everyone else to the coming soon page
        return redirect()->route('coming-soon');
    }

    /**
     * Check if coming soon mode is active.
     */
    protected function isComingSoonModeActive(): bool
    {
        // Only in production
        if (app()->environment('local', 'development', 'testing')) {
            return false;
        }

        // Check if explicitly enabled/disabled via env
        return config('app.coming_soon_mode', true);
    }

    /**
     * Check if the current route is allowed.
     */
    protected function isAllowedRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        return in_array($routeName, $this->allowedRoutes);
    }

    /**
     * Check if request has a valid bypass token.
     */
    protected function hasBypassToken(Request $request): bool
    {
        $token = config('app.coming_soon_bypass_token');

        if (! $token) {
            return false;
        }

        // Check query parameter or session
        if ($request->query('preview') === $token) {
            session(['coming_soon_bypass' => true]);

            return true;
        }

        return session('coming_soon_bypass', false);
    }
}
