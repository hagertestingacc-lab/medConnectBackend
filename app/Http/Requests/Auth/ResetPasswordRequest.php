<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*\W).{8,}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must contain at least 1 uppercase, 1 number, 1 symbol, and be 8+ characters.',
        ];
    }
}
