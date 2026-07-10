<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class productRequest extends FormRequest
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
            'name'           => 'required|string|max:255',
            'price'          => 'required|numeric|min:0',
            'category_id'           => 'required|exists:category,id',
            'stock'          => 'required|integer|min:0',
            'setup_duration' => [
                'required',
                'string',
                'regex:/min|days/',
            ],
            'description'    => 'required|string',
            'warranty'       => 'nullable|string',
            'configuration'  => 'nullable|string',
            'specification'  => 'nullable|array',
            'restock_date'   => 'nullable|date',
            'is_archive'     => 'boolean',
            'is_rentable'     => 'boolean',

            'images'              => 'array|required',
            'images.*.image'  => 'required|image|file',
            'price_daily' => [
                'numeric',
                'required_with:minimum_rental_days,maximum_rental_days,available_units,preparation_duration',
                'required_if:is_rentable,true'

            ],

            'minimum_rental_days' => [
                'required_with:price_daily,maximum_rental_days,available_units,preparation_duration',
                'integer',
                'min:1',
                'required_if:is_rentable,true',
            ],

            'maximum_rental_days' => [

                'required_with:price_daily,minimum_rental_days,available_units',
                'integer',
                'min:1',
                'gte:minimum_rental_days',
                'required_if:is_rentable,true',
            ],

            'available_units' => [

                'required_with:price_daily,minimum_rental_days,maximum_rental_days',
                'integer',
                'min:0',
                'required_if:is_rentable,true',
            ],

            'preparation_duration' => [
                'string',
                'regex:/min|days/',
                'required_with:price_daily,minimum_rental_days,maximum_rental_days,available_units',
                'required_if:is_rentable,true'
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required'           => 'The product name is required.',
            'price.required'          => 'The price is required.',
            'price.numeric'           => 'The price must be a number.',
            'stock.required'          => 'The stock quantity is required.',
            'stock.integer'           => 'Stock must be an integer.',
            'setup_duration.required' => 'Setup duration is required.',
            'setup_duration.string'  => 'Setup duration must be a text.',
            'setup_duration.regex'      => 'Setup duration must contain "min" or "days".',
            'description.required'    => 'Description is required.',
            'images.required'         => 'At least one image is required.',
            'images.*.image.required' => ' image is required .',
            'images.*.image.image'      => 'The image  must be valid.',


            // price_daily
            'price_daily.required_with' => 'The daily price is required when any rental field is provided.',
            'price_daily.numeric'      => 'The daily price must be a number.',

            // minimum_rental_days
            'minimum_rental_days.required_with' => 'Minimum rental days are required when any rental field is provided.',
            'minimum_rental_days.integer'       => 'Minimum rental days must be an integer.',
            'minimum_rental_days.min'           => 'Minimum rental days must be at least 1.',

            // maximum_rental_days

            'maximum_rental_days.integer'       => 'Maximum rental days must be an integer.',
            'maximum_rental_days.min'           => 'Maximum rental days must be at least 1.',
            'maximum_rental_days.gte'           => 'Maximum rental days must be greater than or equal to minimum rental days.',

            // available_units

            'available_units.integer'       => 'Available units must be an integer.',
            'available_units.min'           => 'Available units cannot be negative.',

            // preparation_duration
            'preparation_duration.string'         => 'Preparation duration must be a string.',
            'preparation_duration.regex'          => 'Preparation duration must contain "min" or "days".',


        ];
    }
}
