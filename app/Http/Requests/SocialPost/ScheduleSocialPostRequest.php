<?php

namespace App\Http\Requests\SocialPost;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('socialPost'));
    }

    /**
     * Convert the scheduled_at from brand timezone to UTC before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('scheduled_at') && $this->scheduled_at) {
            $brand = $this->route('socialPost')->brand;
            $timezone = $brand->timezone ?? 'America/New_York';

            // Parse the datetime in the brand's timezone and convert to UTC
            $scheduledAt = Carbon::parse($this->scheduled_at, $timezone)->setTimezone('UTC');

            $this->merge([
                'scheduled_at' => $scheduledAt->toDateTimeString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'The scheduled date must be in the future.',
        ];
    }
}
