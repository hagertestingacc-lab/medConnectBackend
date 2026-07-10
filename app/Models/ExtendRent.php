<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtendRent extends Model
{  
      protected $table = "extend_rents";

    protected $fillable = [
        'item_id',
        'extend_day',
        'prev_day',
        'invoice_key',
        'status',
        'amount',
        'payment_method',
    ];

    protected $casts = [
        'extend_day' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'item_id');
    }
}