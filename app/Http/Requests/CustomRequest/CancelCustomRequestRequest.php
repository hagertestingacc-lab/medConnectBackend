<?php

namespace App\Http\Requests\CustomRequest;

use Illuminate\Foundation\Http\FormRequest;

class CancelCustomRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
