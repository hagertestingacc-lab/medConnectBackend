<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $message = $this->resource;

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'message' => $message->body,
            'product_id' => $message->product_id,
            'product_name' => $message->product?->name,
            'product_image' => $message->product?->image->first()?->image,
            'sender' => $message->sender,
            'read_at' => $message->read_at,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at,
        ];
    }
}
