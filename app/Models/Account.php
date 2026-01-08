<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get all users belonging to this account.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get all brands belonging to this account.
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * Get all pending invitations for this account.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(AccountInvitation::class);
    }

    /**
     * Get all admin users for this account.
     */
    public function admins(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'admin');
    }

    /**
     * Check if a user is an admin of this account.
     */
    public function isAdmin(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    /**
     * Check if a user is a member of this account.
     */
    public function hasMember(User $user): bool
    {
        return $this->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * Get the user's role in this account.
     */
    public function getUserRole(User $user): ?string
    {
        $membership = $this->users()
            ->where('users.id', $user->id)
            ->first();

        return $membership?->pivot->role;
    }
}
