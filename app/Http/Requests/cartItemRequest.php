<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class cartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:sale',
/*             'rental_start_date' => 'required_if:type,rental|date|before_or_equal:rental_end_date',
            'rental_end_date' => 'required_if:type,rental|date|after_or_equal:rental_start_date',
 */        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product selection is required.',
            'product_id.exists' => 'The selected product does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'type.required' => 'Type is required.',
            'type.in' => 'Type must be either sale or rental.',
            'rental_start_date.required_if' => 'Rental start date is required when the type is rental.',
            'rental_start_date.date' => 'Rental start date must be a valid date.',
            'rental_start_date.before_or_equal' => 'Rental start date must be before or equal to rental end date.',
            'rental_end_date.required_if' => 'Rental end date is required when the type is rental.',
            'rental_end_date.date' => 'Rental end date must be a valid date.',
            'rental_end_date.after_or_equal' => 'Rental end date must be after or equal to rental start date.',
        ];
    }
}
