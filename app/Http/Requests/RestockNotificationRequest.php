<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestockNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:product,id',
        ];
    }
}