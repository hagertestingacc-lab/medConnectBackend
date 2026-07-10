<?php

namespace App\Models;

use App\Models\ProductPart\Product;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table="category";

    protected $fillable = [
        "name",
        "image",
        "cloudinary_image",
        "description",
        "is_active"
    ];

    protected $casts = [
        'is_active'=>'boolean'
    ];

    public function product()
{
 return $this->hasMany(Product::class,'product_id');
 }


   public function scopeActive($query)
    {
    return $query->where('is_active', true);
    }


}
