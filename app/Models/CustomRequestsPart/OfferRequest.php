<?php

namespace App\Models\CustomRequestsPart;

use App\Models\CustomRequestsPart\CustomRequest;
use App\Models\SupplierPart\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferRequest extends Model
{
    use HasFactory;

    protected $table = 'offerRequest';

    protected $fillable = [
        'request_id',
        'supplier_id',
        'price',
        'delivery_days',
        'notes',
        'status',
    ];


    protected $casts = [
        'price' => 'decimal:2',
        'delivery_days' => 'integer',
    ];


    public function customRequest()
    {
        return $this->belongsTo(CustomRequest::class, 'request_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function scopeRequestStatus($query)
    {
        return $query->with("customRequest:id,status");
    }



}
