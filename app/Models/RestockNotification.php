<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestockNotification extends Model
{
    protected $fillable = [
        'product_id',
        'doctor_id',
        'notified',
    ];

    protected $casts = [
        'notified' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProductPart\Product::class, 'product_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DoctorPart\Doctor::class, 'doctor_id');
    }
}
