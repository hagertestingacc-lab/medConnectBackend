<?php

namespace App\Http\Requests\Restock;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestockNotificationRequest extends FormRequest
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
