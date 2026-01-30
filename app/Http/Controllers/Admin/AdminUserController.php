<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Display a list of all users.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with('accounts.subscriptions')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20);

        $stats = [
            'total' => $users->total(),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => AdminUserResource::collection($users)->resolve(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }
}
