<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();
        $account = $user->currentAccount();

        // Redirect new accounts to subscribe page
        // (they'll have trial_ends_at set but no subscription)
        if ($account && $account->onTrial() && ! $account->subscribed('default')) {
            return redirect('/billing/subscribe');
        }

        return redirect()->intended('/dashboard');
    }
}
