<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class cartRentalDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
            'rental_start_date' => 'required|date|before_or_equal:rental_end_date|after:tomorrow',
            'rental_end_date' => 'required|date|after_or_equal:rental_start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'rental_start_date.required' => 'Rental start date is required.',
            'rental_start_date.date' => 'Rental start date must be a valid date.',
            'rental_start_date.before_or_equal' => 'Rental start date must be before or equal to rental end date.',
            'rental_end_date.required' => 'Rental end date is required.',
            'rental_end_date.date' => 'Rental end date must be a valid date.',
            'rental_end_date.after_or_equal' => 'Rental end date must be after or equal to rental start date.',
        ];
    }
}
