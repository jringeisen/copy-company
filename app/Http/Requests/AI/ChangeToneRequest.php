<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class ChangeToneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentBrand() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'tone' => ['required', 'string', 'in:formal,casual,professional,friendly,persuasive'],
        ];
    }
}
