<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DisableSsrForAuthenticated
{
    /**
     * Disable SSR for authenticated users.
     *
     * SSR is only beneficial for guest pages (SEO, initial load for public pages).
     * Authenticated pages don't need SSR since they're not indexed by search engines.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            Inertia::withoutSsr();
        }

        return $next($request);
    }
}
