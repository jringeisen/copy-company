<?php

namespace App\Http\Middleware;

use App\Models\OAuthTokenContext;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionsTeamContext
{
    /**
     * Set the Spatie Permission team context based on the user's current account.
     *
     * Supports both session-based and OAuth token-based context resolution.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $account = $this->resolveAccount($request);

            if ($account) {
                setPermissionsTeamId($account->id);
            }
        }

        return $next($request);
    }

    /**
     * Resolve the account from OAuth token context or session.
     */
    protected function resolveAccount(Request $request): ?\App\Models\Account
    {
        $user = $request->user();

        // Check if authenticated via Passport OAuth token
        $token = $user->token();

        if ($token instanceof Token) {
            // Look up the brand context for this token
            $context = OAuthTokenContext::find($token->id);

            if ($context) {
                // Get the account through the brand
                $brand = $context->brand;

                if ($brand) {
                    return $brand->account;
                }
            }
        }

        // Fall back to session-based account (for web/Sanctum authentication)
        return $user->currentAccount();
    }
}
