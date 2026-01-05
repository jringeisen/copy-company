<?php

namespace App\Http\Requests\MediaFolder;

use App\Models\MediaFolder;
use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->currentBrand() !== null;
    }

    public function rules(): array
    {
        $brandId = $this->user()->currentBrand()?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($brandId): void {
                    if ($value && ! MediaFolder::where('id', $value)->where('brand_id', $brandId)->exists()) {
                        $fail('The selected parent folder does not exist.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a folder name.',
            'name.max' => 'Folder name must be less than 100 characters.',
        ];
    }
}
