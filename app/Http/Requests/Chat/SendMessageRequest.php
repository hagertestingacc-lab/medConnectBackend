<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => 'required|exists:user,id',
            'message' => 'required|string|max:5000',
            'product_id' => 'nullable|exists:product,id',
        ];
    }
}
