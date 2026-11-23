<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource->toArray();

        if ($this->resource->relationLoaded('items')) {
            $data['items'] = OrderItemResource::collection($this->resource->items)->resolve();
        }

        return $data;
    }
}
