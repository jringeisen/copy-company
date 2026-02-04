<?php

namespace App\Http\Requests\Strategy;

use Illuminate\Foundation\Http\FormRequest;

class ConvertSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('marketingStrategy'));
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'index' => ['required', 'integer', 'min:0'],
        ];
    }
}
