<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all accounts this user belongs to.
     */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the user's current account.
     */
    public function currentAccount(): ?Account
    {
        $accountId = session('current_account_id');

        if ($accountId) {
            $account = $this->accounts()->find($accountId);
            if ($account) {
                return $account;
            }
        }

        return $this->accounts()->first();
    }

    /**
     * Switch to a different account.
     */
    public function switchAccount(Account $account): void
    {
        if ($this->accounts()->where('accounts.id', $account->id)->exists()) {
            session(['current_account_id' => $account->id]);
            session(['current_brand_id' => null]);
        }
    }

    /**
     * Get all brands accessible to this user through their current account.
     */
    public function brands(): HasMany
    {
        $account = $this->currentAccount();
        if (! $account) {
            return $this->hasMany(Brand::class, 'account_id')->whereRaw('1 = 0');
        }

        return $account->brands();
    }

    /**
     * Get the user's current brand.
     */
    public function currentBrand(): ?Brand
    {
        $account = $this->currentAccount();
        if (! $account) {
            return null;
        }

        $brandId = session('current_brand_id');

        if ($brandId) {
            $brand = $account->brands()->find($brandId);
            if ($brand) {
                return $brand;
            }
        }

        return $account->brands()->first();
    }

    /**
     * Switch to a different brand.
     */
    public function switchBrand(Brand $brand): void
    {
        $account = $this->currentAccount();
        if ($account && $account->brands()->where('brands.id', $brand->id)->exists()) {
            session(['current_brand_id' => $brand->id]);
        }
    }

    /**
     * Check if the user is an admin of their current account.
     */
    public function isAccountAdmin(): bool
    {
        $account = $this->currentAccount();

        return $account?->isAdmin($this) ?? false;
    }

    /**
     * Get the user's role in their current account.
     */
    public function getAccountRole(): ?string
    {
        $account = $this->currentAccount();

        return $account?->getUserRole($this);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
