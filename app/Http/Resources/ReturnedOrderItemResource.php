<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnedOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->resource['order']->id,
            'product_id' => $this->resource['item']->product_id,
            'quantity' => $this->resource['item']->quantity,
            'available_units' => $this->resource['rentalDetails']->available_units,
            'stock_units' => $this->resource['rentalDetails']->stock_units,
        ];
    }
}
