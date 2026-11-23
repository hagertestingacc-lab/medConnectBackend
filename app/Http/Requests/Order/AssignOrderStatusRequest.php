<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class AssignOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:processing,ready',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'invalid status value',
        ];
    }
}
