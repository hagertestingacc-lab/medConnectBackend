<?php

namespace App\Models;

use App\Models\DoctorPart\Doctor;
use App\Models\ProductPart\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'cart';

    protected $fillable = [
        'doctor_id',
        'product_id',
        'quantity',
        'type',
        'rental_start_date',
        'rental_end_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'type' => 'string',
        'rental_start_date' => 'date',
        'rental_end_date' => 'date',
    ];

    public function validateRent(): ?string
    {
        if (! $this->product) {
            return 'Product not found.';
        }

        if (! $this->rental_start_date || ! $this->rental_end_date) {
            return 'Rental start date and rental end date are required.';
        }

        return $this->product->validateRent([
            'quantity' => $this->quantity,
            'rental_start_date' => $this->rental_start_date->toDateString(),
            'rental_end_date' => $this->rental_end_date->toDateString(),
        ]);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
