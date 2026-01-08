<?php

namespace App\Http\Requests\Media;

use App\Models\MediaFolder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Media::class);
    }

    public function rules(): array
    {
        $brandId = $this->user()->currentBrand()?->id;

        return [
            'images' => ['required', 'array', 'max:10'],
            'images.*' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'gif', 'webp'])
                    ->max(10 * 1024), // 10MB
            ],
            'folder_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($brandId): void {
                    if ($value && ! MediaFolder::where('id', $value)->where('brand_id', $brandId)->exists()) {
                        $fail('The selected folder does not exist.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Please select at least one image to upload.',
            'images.max' => 'You can upload a maximum of 10 images at once.',
            'images.*.max' => 'Each image must be less than 10MB.',
            'images.*.types' => 'Only JPG, PNG, GIF, and WebP images are allowed.',
        ];
    }
}
