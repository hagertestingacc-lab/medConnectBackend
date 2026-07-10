<?php

namespace App\Models\ProductPart;

use Illuminate\Database\Eloquent\Model;

class ProductRentalDetails extends Model
{
    protected $table="productRentalDetails";

    protected $fillable = [
        "product_id",
        "price_daily",
        "minimum_rental_days",
        "maximum_rental_days",
        "available_units",
        "stock_units",
        "extends_days_rent",
        "preparation_duration"
    ];

     public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }

}