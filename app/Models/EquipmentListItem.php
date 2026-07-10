<?php

namespace App\Models;

use App\Models\ProductPart\Product;
use Illuminate\Database\Eloquent\Model;

class EquipmentListItem extends Model
{
    protected $table = 'equipment_list_items';

    protected $fillable = [
        'list_id',
        'product_id',
        'is_ava',
    ];

    protected $casts = [
        'is_ava' => 'boolean',
    ];

    public $timestamps = false; // no created_at updated_at

    public function list()
    {
        return $this->belongsTo(EquipmentList::class, 'list_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
