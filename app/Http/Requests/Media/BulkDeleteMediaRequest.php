<?php

namespace App\Http\Requests\Media;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentBrand() !== null;
    }

    public function rules(): array
    {
        $brandId = $this->user()->currentBrand()?->id;

        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($brandId): void {
                    if (! Media::where('id', $value)->where('brand_id', $brandId)->exists()) {
                        $fail('One or more selected images do not exist.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Please select at least one image to delete.',
            'ids.min' => 'Please select at least one image to delete.',
        ];
    }
}
