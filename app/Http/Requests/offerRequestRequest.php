<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class offerRequestRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('price') && !$this->filled('supplier_price')) {
            $this->merge(['supplier_price' => $this->input('price')]);
        }
    }

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
            'price' => 'required|numeric|min:0',
            'delivery_days' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'The supplier price is required.',
            'price.numeric' => 'The supplier price must be a valid number.',
            'price.min' => 'The supplier price must be at least 0.',
            'delivery_days.integer' => 'Delivery days must be a whole number.',
            'delivery_days.min' => 'Delivery days must be at least 0.',
            'notes.string' => 'Notes must be a valid text value.',
        ];
    }
}