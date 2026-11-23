<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $conversation = $this->resource;

        return [
            'id' => $conversation->id,
            'other_user' => $conversation->otherUser($request->user()->id),
            'last_message_at' => $conversation->last_message_at,
        ];
    }
}
