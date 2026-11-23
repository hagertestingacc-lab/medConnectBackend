<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:create_pending,create_accepted,create_rejected,edit_pending,edit_accepted,edit_rejected',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The avaliable status is create_accepted create_rejected edit_pending edit_accepted edit_rejected',
        ];
    }
}
