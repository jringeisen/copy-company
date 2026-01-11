<?php

namespace App\Http\Resources;

use App\Models\LoopSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoopSchedule
 */
class LoopScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week->value,
            'day_of_week_display' => $this->day_of_week->displayName(),
            'time_of_day' => $this->time_of_day,
            'time_display' => Carbon::parse($this->time_of_day)->format('g:i A'),
            'platform' => $this->platform?->value,
        ];
    }
}
