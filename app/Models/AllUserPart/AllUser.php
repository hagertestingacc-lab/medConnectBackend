<?php

namespace App\Models\AllUserPart;

use App\Models\Chat\Conversation;
use App\Models\DoctorPart\Doctor;
use App\Models\SupplierPart\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;

class AllUser extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $table = "user";

    protected $fillable = [
        'role',
        'status',
        'fullname',
        'email',
        'password',
        'email_verified_at',
        'address',
        "role"
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            /*       'role' =>'App\Enums\UserRole',
            'status'=>'App\Enums\UserStatus' */
        ];
    }




    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'user_table_id');
    }
    public function supplier()
    {
        return $this->hasOne(Supplier::class, 'user_table_id');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    public function isSupplier()
    {
        return $this->role === 'supplier';
    }
    public function isDoctor()
    {
        return $this->role === 'doctor';
    }
      public function userOne()
    {
        return $this->belongsTo(Conversation::class, 'participant_one');
    }

    public function userTwo()
    {
        return $this->belongsTo(Conversation::class, 'participant_two');
    }

}
