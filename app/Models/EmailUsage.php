<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'emails_sent',
        'period_date',
        'reported_to_stripe',
        'reported_at',
    ];

    protected $casts = [
        'period_date' => 'date',
        'reported_to_stripe' => 'boolean',
        'reported_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Record an email sent for the given account.
     */
    public static function recordEmailSent(Account $account): void
    {
        self::query()
            ->updateOrCreate(
                [
                    'account_id' => $account->id,
                    'period_date' => now()->toDateString(),
                ],
                ['emails_sent' => 0]
            );

        self::query()
            ->where('account_id', $account->id)
            ->where('period_date', now()->toDateString())
            ->increment('emails_sent');
    }

    /**
     * Get unreported usage for an account.
     */
    public static function getUnreportedUsage(Account $account): int
    {
        return (int) self::query()
            ->where('account_id', $account->id)
            ->where('reported_to_stripe', false)
            ->sum('emails_sent');
    }
}
