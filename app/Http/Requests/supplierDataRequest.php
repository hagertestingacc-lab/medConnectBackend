<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\Rules\Phone;
class supplierDataRequest extends FormRequest
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
            'address' => 'required|string|min:10|max:255',
            'national_id' => 'required|string|size:14|unique:supplier,national_id',
            'phone' => ['required',"phone:EG","unique:supplier,phone"],
            'certificate_name' => 'required|string|max:100',
            'certificate_image' => 'required|image',
            'company_image_url' => 'required|image',
            'company_name' => 'required|string|max:200',
            'governorate' => 'required|string|max:50',
            'tax_card_image' => 'required|image',
        ];
    }
    public function messages()
    {
        return [
            // National ID
            'national_id.required'   => 'The national ID field is required.',
            'national_id.string'     => 'The national ID must be a valid string.',
            'national_id.size'       => 'The national ID must be exactly 14 characters.',
            'national_id.unique'     => 'This national ID is already registered in our system.',
            'password.regex' => 'Password must contain at least 1 uppercase, 1 number, 1 symbol, and be 8+ characters.',

            // Phone
            'phone.required'         => 'Please provide a phone number.',
            'phone.string'           => 'The phone number must be a valid string.',
             'phone.phone' => 'Phone number is unvalid',
  
            // Company Image URL
            'company_image_url.required' => 'The company image is required.',
            'company_image_url.image'      => 'Please enter a valid company image',

            // Company Name
            'company_name.required'  => 'The company name is required.',
            'company_name.string'    => 'The company name must be a valid string.',
            'company_name.max'       => 'The company name may not exceed 200 characters.',

            // Governorate
            'governorate.required'   => 'The governorate field is required.',
            'governorate.string'     => 'The governorate must be a valid string.',
            'governorate.max'        => 'The governorate name may not be longer than 50 characters.',


            // Tax Card Image
            'tax_card_image.required' => 'The tax card image is required.',
            'tax_card_image.image'      => 'Please provide a valid  tax card image.',
            //certificate

            'certificate_image.image'      => 'Please provide a valid  certificate image.',
            "certificate_name.required" => "certificate name is required.",
            'certificate_name.string'     => 'The certificate name must be a valid string.',
            'certificate_name.size'       => 'The certificate_name must at most 100 characters.',
        ];
    }
}