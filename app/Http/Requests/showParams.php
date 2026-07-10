<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class showParams extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'filter_by'   => 'sometimes|in:id,name,description,is_active,created_at,category_id',
        'sort_by'     => 'sometimes|in:id,name,description,created_at,category_id',
        'sort_order'  => 'sometimes|in:asc,desc',
        'per_page'    => 'sometimes|integer|min:1|max:100',
        // filter_value may also need validation (e.g., string, sometimes)
        'filter_value' => 'sometimes|string|max:255',
        ];
    }


       public function messages(): array
    {
        return [
            'filter_by.in'    => 'The filter_by parameter must be one of: id,name,description,is_active,created_at,category_id' ,
            'sort_by.in'      => 'The sort_by parameter must be one of: id,name,description,created_at,category_id' ,
            'sort_order.in'   => 'The sort_order parameter must be either asc or desc.',
            'per_page.integer' => 'The per_page parameter must be an integer.',
            'per_page.min'     => 'The per_page parameter must be at least 1.',
            'per_page.max'     => 'The per_page parameter may not exceed 100.',
            'filter_value.string' => 'The filter_value must be a string.',
            'filter_value.max'    => 'The filter_value may not exceed 255 characters.',
        ];
    }
}
