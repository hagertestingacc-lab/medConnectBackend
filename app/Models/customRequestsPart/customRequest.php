<?php

namespace App\Models\customRequestsPart;

use App\Models\DoctorPart\Doctor;
use App\Models\customRequestsPart\OfferRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class customRequest extends Model
{
    use HasFactory;

    protected $table = 'customRequest';

    protected $fillable = [
        'doctor_id',
        'additionalDetails',
        'budget',
        'item',
        'type',
        'expires_at',
        'rent_start_date',
        'rent_end_date',
        'status',
    ];

    protected $casts = [
        'item'          => 'array',          // automatically encode/decode JSON
        'budget'        => 'decimal:2',
        'expires_at'    => 'date',
        'rent_start_date' => 'date',
        'rent_end_date'   => 'date',
    ];

    // Relationship with Doctor
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function offerRequest()
    {
        return $this->hasMany(OfferRequest::class, 'request_id');
    }
    // Optional: scope for active/not expired requests
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>=', now())
                     ->whereNotIn('status', ['expired', 'cancelled', 'delivered']);
    }
    public function scopeStatus($query,$status)
    {
        if($status!="all")
        return $query->where('status',$status );
    }





}
