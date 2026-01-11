<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Enums\SocialPlatform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoopSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'loop_id',
        'day_of_week',
        'time_of_day',
        'platform',
    ];

    protected $casts = [
        'day_of_week' => DayOfWeek::class,
        'platform' => SocialPlatform::class,
    ];

    /**
     * @return BelongsTo<Loop, $this>
     */
    public function loop(): BelongsTo
    {
        return $this->belongsTo(Loop::class);
    }

    /**
     * Get a human-readable description of this schedule.
     */
    public function getDescriptionAttribute(): string
    {
        $day = $this->day_of_week->displayName();
        $time = \Carbon\Carbon::parse($this->time_of_day)->format('g:i A');

        return "{$day} at {$time}";
    }
}
