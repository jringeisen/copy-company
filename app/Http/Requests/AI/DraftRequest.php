<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class DraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentBrand() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'bullets' => ['nullable', 'string'],
        ];
    }
}
