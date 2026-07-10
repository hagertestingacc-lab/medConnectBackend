<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class doctorDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => 'required|string|min:2|max:120',
            'email' => 'required|email:rfc,dns|unique:user,email|max:255',
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*\W).{8,}$/', 'confirmed'],
            'profile_image_url' => 'image',
            'address' => 'required|string|min:10|max:255',
            'national_id' => 'required|string|size:14',
            'phone' => ['required',"phone:EG","unique:doctors,phone"],
            'license_number' => 'required|string|max:50',
        ];
    }

    public function messages()
    {
        return [
            // Fullname
            'full_name.required' => 'Full name is required',
            'full_name.min' => 'Full name must be at least 2 characters',
            'full_name.max' => 'Full name cannot exceed 120 characters',

            // Email
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'email.max' => 'Email address is too long',

            // Password
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.regex' => 'Password must contain at least 1 uppercase, 1 number, 1 symbol, and be 8+ characters.',
            // Profile image
            'profile_image_url.image' => 'Please provide a valid image ',

            // Address
            'address.required' => 'Street address is required',
            'address.max' => 'Address is too long',

            // National ID
            'national_id.required' => 'National ID is required',
            'national_id.size' => 'National ID must be exactly 14 digits',

            // Phone
            'phone.required' => 'Phone number is required',
            'phone.max' => 'Phone number is too long',
            'phone.phone' => 'Phone number is unvalid',

            // License
            'license_number.required' => 'Medical license number is required',
            'license_number.max' => 'License number is too long',
        ];
    }
}
