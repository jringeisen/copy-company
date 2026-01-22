<?php

namespace App\Listeners;

use App\Models\OAuthTokenContext;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Events\AccessTokenCreated;

class StoreOAuthTokenContext
{
    /**
     * Handle the event.
     *
     * Store the brand context associated with the OAuth token.
     */
    public function handle(AccessTokenCreated $event): void
    {
        // Get the brand_id from the request (set during authorization)
        $brandId = request()->input('brand_id');

        if (! $brandId) {
            return;
        }

        // Get the user who owns the token
        $user = User::find($event->userId);

        if (! $user) {
            Log::warning('StoreOAuthTokenContext: User not found for token', [
                'user_id' => $event->userId,
                'token_id' => $event->tokenId,
            ]);

            return;
        }

        // Security: Verify the user has access to the selected brand
        $account = $user->currentAccount();

        if (! $account) {
            Log::warning('StoreOAuthTokenContext: User has no current account', [
                'user_id' => $event->userId,
                'token_id' => $event->tokenId,
            ]);
            throw new AuthorizationException('No account found for user');
        }

        $brandBelongsToUser = $account->brands()->where('id', $brandId)->exists();

        if (! $brandBelongsToUser) {
            Log::warning('StoreOAuthTokenContext: User does not have access to brand', [
                'user_id' => $event->userId,
                'brand_id' => $brandId,
                'token_id' => $event->tokenId,
            ]);
            throw new AuthorizationException('Invalid brand selection');
        }

        // Store the brand context for this token
        OAuthTokenContext::create([
            'access_token_id' => $event->tokenId,
            'brand_id' => $brandId,
        ]);

        Log::info('StoreOAuthTokenContext: Brand context stored for OAuth token', [
            'user_id' => $event->userId,
            'brand_id' => $brandId,
            'token_id' => $event->tokenId,
        ]);
    }
}
