<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class productUpdateRequest extends FormRequest
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
                         $product = $this->route('product');

        return [
            'name' => [
            'required',
            'string',
            'max:255',
            Rule::unique('product')
                ->where(fn ($query) => $query->where('supplier_id', $this->user()->supplier->id))
                ->ignore($this->route('product')),
        ],
            'price'          => 'numeric|min:0',
            'category_id'           => 'exists:category,id',
            'stock'          => 'integer|min:0',
            'setup_duration.string'  => 'Setup duration must be a text.',
            'setup_duration.regex'     => 'Setup duration must contain "min" or "days".',
            'description'    => 'string',
            'warranty'       => 'nullable|string',
            'configuration'  => 'nullable|string',
            'specification'  => 'nullable|array',
            'is_rentable'    => 'boolean',
            'restock_date'   => 'nullable|date',
            'is_archive'     => 'boolean',
            'images'              => 'array',
            'images.*.image'  => 'image|file',

            'price_daily' => Rule::requiredIf(!$product->rentalDetails()->exists() && $this->hasAny(["minimum_rental_days","maximum_rental_days","available_units","rental_preparation_duration"]) ) . '|numeric',

            'minimum_rental_days'    => Rule::requiredIf(!$product->rentalDetails()->exists() && $this->hasAny(["price_daily","maximum_rental_days","available_units","rental_preparation_duration"]) ) . '|integer|min:1',

            'maximum_rental_days'    => Rule::requiredIf(!$product->rentalDetails()->exists() && $this->hasAny(["minimum_rental_days","price_daily","available_units","rental_preparation_duration"]) )  . '|integer|min:1|gte:minimum_rental_days',

            'available_units' => Rule::requiredIf(!$product->rentalDetails()->exists() && $this->hasAny(["minimum_rental_days","maximum_rental_days","price_daily","rental_preparation_duration"]) ) . '|integer|min:0',

             'preparation_duration' => [
    Rule::requiredIf(!$product->rentalDetails()->exists() && $this->hasAny(["minimum_rental_days","maximum_rental_days","available_units","price_daily"])),
    'string',
    'regex:/^\d+(min|days)$/'
],

        ];
    }

        public function messages()
    {
        return [
            'price_daily.required_if' => 'The daily price is required because you provided other rental details and this product is not rentable.',
    'price_daily.numeric'     => 'The daily price must be a number.',

    // minimum_rental_days
    'minimum_rental_days.required_if'    => 'Minimum rental days are required because you provided other rental details and this product is not rentable.',
    'minimum_rental_days.integer'        => 'Minimum rental days must be an integer.',
    'minimum_rental_days.min'            => 'Minimum rental days must be at least 1.',

    // maximum_rental_days
    'maximum_rental_days.required_if'    => 'Maximum rental days are required because you provided other rental details and this product is not rentable.',
    'maximum_rental_days.integer'        => 'Maximum rental days must be an integer.',
    'maximum_rental_days.min'            => 'Maximum rental days must be at least 1.',
    'maximum_rental_days.gte'            => 'Maximum rental days must be greater than or equal to minimum rental days.',

    // available_units
    'available_units.required_if' => 'Available units are required because you provided other rental details and this product is not rentable.',
    'available_units.integer'     => 'Available units must be an integer.',
    'available_units.min'         => 'Available units cannot be negative.',

    // rental_preparation_duration
    'preparation_duration.required_if' => 'Preparation duration is required because you provided other rental details and this product is not rentable.',
    'preparation_duration.string'     => 'Preparation duration must be a string.',
    'preparation_duration.regex'      => 'Preparation duration must contain min or days without spacing.',
];


    }



public function withValidator($validator)
{
    $product = $this->route('product'); // get the product model (route model binding)

    $validator->after(function ($validator) use ($product) {
        $isRentable = $this->input('is_rentable', $product->is_rentable);
        $hasRentalInDb = $product->rentalDetails()->exists();
        $hasRentalInRequest = $this->hasAny([
            'price_daily', 'minimum_rental_days', 'maximum_rental_days',
            'available_units', 'rental_preparation_duration'
        ]);

        if ($isRentable && !$hasRentalInDb && !$hasRentalInRequest) {
            $validator->errors()->add('is_rentable', 'When the product is rentable, you must provide rental details or it must already have them.');
            }
    });
}


}