<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeamContext
{
    /**
     * Set the Spatie Permission team context based on the user's current account.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $account = $request->user()->currentAccount();
            if ($account) {
                setPermissionsTeamId($account->id);
            }
        }

        return $next($request);
    }
}
