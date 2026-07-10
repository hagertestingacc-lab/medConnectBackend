<?php

namespace App\Models\DoctorPart;

use App\Models\AllUserPart\AllUser;
use App\Models\Cart;
use App\Models\customRequestsPart\customRequest;
use App\Models\ProductPart\Review;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasFactory, HasApiTokens;


    protected $table = "doctors";


    protected $fillable = [
        'user_table_id',
        'license_table_id',
        'phone',
        'profile_image_url',
        'cloudinary_profile_img_id',
        'is_verified',

    ];



    protected $casts = [
        'is_verified' => 'boolean'
    ];


    public function doctorLicense()
    {
        return $this->belongsTo(DoctorLicense::class, 'license_table_id', 'license_number');
    }
    public function allUser()
    {
        return $this->belongsTo(AllUser::class, 'user_table_id');
    }
    public function customRequest()
    {
        return $this->hasMany(customRequest::class, 'doctor_id');
    }
    public function cart()
    {
        return $this->hasMany(Cart::class, 'doctor_id');
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'doctor_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'doctor_id');
    }

    public function restockNotifications()
    {
        return $this->hasMany(\App\Models\RestockNotification::class, 'doctor_id');
    }
}
