<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized. Admin access required.');
        }

        if (! $this->isLocalEnvironment() && ! $this->isAdmin($user->email)) {
            abort(403, 'Unauthorized. Admin access required.');
        }

        return $next($request);
    }

    /**
     * Check if the application is running in local environment.
     */
    protected function isLocalEnvironment(): bool
    {
        return app()->environment('local');
    }

    /**
     * Check if the given email is in the admin list.
     */
    protected function isAdmin(string $email): bool
    {
        $adminEmails = config('admin.emails', []);

        return in_array($email, $adminEmails, true);
    }
}
