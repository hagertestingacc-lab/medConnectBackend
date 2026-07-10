<?php

namespace App\Models;

use App\Models\ProductPart\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_applied',
        'final_price',
        'rental_start',
        'rental_end',
        'extend_day',
        'sub_status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'final_price' => 'decimal:2',
        'rental_start' => 'date',
        'rental_end' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }


            public function extendRent()
        {
            return $this->hasOne(ExtendRent::class, 'item_id');
        }
    public function scopeForSupplier(EloquentBuilder   $query, $supplierId)
    {
        return $query->whereHas("product", function ($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId);
        });
    }
}
