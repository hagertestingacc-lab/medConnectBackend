<?php

namespace App\Models\ProductPart;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{

 protected $table= "product_image";


 protected $fillable = [
        'product_id',
        'image',
        "cloudinary_image_id"
    ];


 public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }

}