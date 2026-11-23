<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,confirmed,paid,processing,ready,shipped,delivered,cancelled,returned',
            'sub_status' => 'required|in:pending,confirmed,paid,processing,ready,shipped,delivered,cancelled,returned,unReturned',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The order status is not valid.',
            'sub_status.in' => 'The item sub-status is not valid.',
        ];
    }
}
