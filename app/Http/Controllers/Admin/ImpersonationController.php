<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if ($admin->id === $user->id) {
            return back()->with('error', 'You cannot impersonate yourself.');
        }

        if ($request->session()->has('impersonating_from')) {
            return back()->with('error', 'You are already impersonating a user. Stop the current session first.');
        }

        $request->session()->put('impersonating_from', $admin->id);
        $request->session()->forget(['current_account_id', 'current_brand_id']);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Stop impersonating and restore the admin session.
     */
    public function stop(Request $request): RedirectResponse
    {
        $adminId = $request->session()->get('impersonating_from');

        if (! $adminId) {
            return redirect()->route('dashboard')->with('error', 'No active impersonation session.');
        }

        $admin = User::find($adminId);

        if (! $admin) {
            $request->session()->forget('impersonating_from');

            return redirect()->route('dashboard')->with('error', 'Admin user not found.');
        }

        $request->session()->forget(['impersonating_from', 'current_account_id', 'current_brand_id']);

        Auth::login($admin);

        return redirect()->route('admin.users.index');
    }
}
