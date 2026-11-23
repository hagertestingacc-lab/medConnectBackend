<?php

namespace App\Http\Resources;

use App\Models\Chat\Conversation;
use App\Models\SupplierPart\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $supplier = $this->resource;
        $data = $supplier->toArray();
        $user = $request->user();

        if (!$user || !$supplier->user_table_id) {
            $data['conversation_id'] = null;
            return $data;
        }

        $data['conversation_id'] = $this->resolveConversationId($supplier, $user->id);

        return $data;
    }

    private function resolveConversationId(Supplier $supplier, int $userId): ?int
    {
        $conversation = Conversation::find($userId, $supplier->user_table_id);

        return $conversation->id ?? null;
    }
}
