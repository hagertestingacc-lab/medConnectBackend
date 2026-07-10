<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_type' => 'required|in:sale,rental',
            'payment_type' => 'required|in:cash,online',
            'product_id' => Rule::requiredIf($this->order_type=="rental").'|exists:product,id',
            'quantity' => Rule::requiredIf($this->order_type=="rental").'|integer|min:1',
            'rental_start_date' => Rule::requiredIf($this->order_type=="rental").'|date',
            'rental_end_date' => Rule::requiredIf($this->order_type=="rental").'|date',
        ];
    }

    public function messages(): array
    {
        return [
            'order_type.required' => 'Order type is required.',
            'order_type.in' => 'Order type must be sale or rental.',
            'product_id.exists' => 'The selected product does not exist.',
            'product_id.required' => 'The product is required.',
        ];
    }
}