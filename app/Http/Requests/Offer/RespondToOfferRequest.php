<?php

namespace App\Http\Requests\Offer;

use Illuminate\Foundation\Http\FormRequest;

class RespondToOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response' => 'required|in:rejected,accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'response.in:should be rejected or accepted',
        ];
    }
}
