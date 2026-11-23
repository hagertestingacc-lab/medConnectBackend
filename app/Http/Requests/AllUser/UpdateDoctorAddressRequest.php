<?php

namespace App\Http\Requests\AllUser;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address' => 'string|required',
        ];
    }
}
