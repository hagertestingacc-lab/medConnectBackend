<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|min:3|unique:category,name',
            'description' => 'sometimes|string|min:10',
            'is_active' => 'sometimes|boolean',
            'image' => 'sometimes|image',
        ];
    }
}
