<?php

namespace App\Http\Requests\Loop;

use Illuminate\Foundation\Http\FormRequest;

class ReorderLoopItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('loop'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*' => ['required', 'integer', 'exists:loop_items,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Please provide the item order.',
            'items.*.exists' => 'One or more items do not exist.',
        ];
    }
}
