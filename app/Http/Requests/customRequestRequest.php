<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class customRequestRequest extends FormRequest
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
            'additionalDetails'   => 'string',
            'budget'              => 'numeric|min:0',
            'item'                => 'required|array',
            'type'                => ['required', Rule::in(['rental', 'tools', 'paid devices'])],
            'expires_at'          => 'required|date|after:today',
            'rent_start_date'     => [
                'nullable',
                'date',
                Rule::requiredIf($this->type === 'rental'),
                'after_or_equal:today',
            ],
            'rent_end_date'       => [
                'nullable',
                'date',
                Rule::requiredIf($this->type === 'rental'),
                'after_or_equal:rent_start_date',
                "after_or_equal:expires_at"
            ],
        ];
    }


     public function messages()
    {
        return [
            'budget.min'            => 'The budget cannot be negative.',
            'item.required'         => 'Item details are required.',
            'item.array'             => 'Item must be a valid an array.',
            'type.required'         => 'Request type is required,Type must be rental, tools, or paid devices.',
            'type.in'               => 'Type must be rental, tools, or paid devices.',
            'expires_at.required'   => 'Expiration date is required.',
            'expires_at.after'      => 'Expiration date must be in the future.',
            'rent_start_date.required_if' => 'Rental start date is required for rental requests.',
            'rent_start_date.after_or_equal' => 'Rental start date must be today or later.',
            'rent_end_date.required_if'   => 'Rental end date is required for rental requests.',
            'rent_end_date.after_or_equal' => 'Rental end date must be after or equal to start date and expire date.',
            'status.in'             => 'Invalid status value.',
        ];
    }
}