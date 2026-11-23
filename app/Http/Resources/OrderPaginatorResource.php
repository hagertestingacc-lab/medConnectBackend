<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPaginatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource->toArray();
        $data['data'] = OrderResource::collection($this->resource->getCollection())->resolve();

        return $data;
    }
}
