<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class IndexOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:sale,rental',
        ];
    }

    public function messages(): array
    {
        return [
            'message' => 'type must be sale or rental , by default is sale',
        ];
    }
}
