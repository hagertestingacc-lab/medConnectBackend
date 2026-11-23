<?php

namespace App\Http\Requests\Offer;

use Illuminate\Foundation\Http\FormRequest;

class AssignOfferStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:in negotiation,shipped,delivered',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must not be open and must be one of: in negotiation, shipped, delivered.',
        ];
    }
}
