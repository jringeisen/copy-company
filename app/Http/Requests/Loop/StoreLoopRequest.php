<?php

namespace App\Http\Requests\Loop;

use App\Models\Loop;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Loop::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:instagram,facebook,pinterest,linkedin,tiktok'],
            'is_active' => ['nullable', 'boolean'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedules.*.time_of_day' => ['required', 'date_format:H:i'],
            'schedules.*.platform' => ['nullable', 'string', 'in:instagram,facebook,pinterest,linkedin,tiktok'],
        ];
    }
}
