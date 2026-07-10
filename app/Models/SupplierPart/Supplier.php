<?php

namespace App\Models\SupplierPart;

use App\Models\AllUserPart\AllUser;
use App\Models\customRequestsPart\OfferRequest;
use App\Models\ProductPart\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Supplier extends Authenticatable
{
    use Notifiable ,HasFactory,HasApiTokens;

protected $table = 'supplier';
    protected $fillable = [
        "id",
"user_table_id",
"national_id",
"phone",
'company_name',
"company_image_url",
"certificate_image",
"tax_card_image",
"certificate_name",
"governorate",
"commercial_register_image",
"is_verified",
"cloudinary_company_image_id",
"cloudinary_tax_card_id",
"cloudinary_certificate_id"
    ];

    protected $cast =
    [
        "is_verified"=>"boolean"
    ];



public function allUser()
{
 return $this->belongsTo(AllUser::class,'user_table_id');
 }
public function product()
{
 return $this->hasMany(Product::class,'supplier_id');
 }
   public function offerRequest()
    {
        return $this->hasMany(OfferRequest::class, 'supplier_id');
    }


 
}