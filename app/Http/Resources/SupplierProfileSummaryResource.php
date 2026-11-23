<?php

namespace App\Http\Resources;

use App\Models\Chat\Conversation;
use App\Models\SupplierPart\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierProfileSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $supplier = $this->resource;

        return [
            'id' => $supplier->id,
            'allUser_id' => $supplier->allUser?->id,
            'company_name' => $supplier->company_name,
            'company_image_url' => $supplier->company_image_url,
            'certificate_name' => $supplier->certificate_name,
            'conversation_id' => $this->resolveConversationId($supplier, $request),
        ];
    }

    private function resolveConversationId(Supplier $supplier, Request $request): ?int
    {
        $user = $request->user();
        $user2 = $supplier->allUser;

        if (!$user || !$user2?->id) {
            return null;
        }

        $conversation = Conversation::where(function ($query) use ($user, $user2) {
            $query->where('participant_one', $user->id)
                ->where('participant_two', $user2->id);
        })->orWhere(function ($query) use ($user, $user2) {
            $query->where('participant_one', $user2->id)
                ->where('participant_two', $user->id);
        })->first();

        return $conversation?->id ?? null;
    }
}
