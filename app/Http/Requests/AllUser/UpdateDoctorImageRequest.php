<?php

namespace App\Http\Requests\AllUser;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_image_url' => 'required|image',
        ];
    }
}
