<?php

namespace App\Http\Resources;

use App\Models\Chat\Conversation;
use App\Models\SupplierPart\Supplier;
use Illuminate\Http\Request;

class DoctorProductResource extends ProductResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resource;
        $supplier = $product->supplier;
        $productArray = $product->toArray();
        $productArray['supplier'] = array_merge($supplier?->toArray() ?? [], [
            'allUserId' => $supplier?->allUser?->id,
            'conversationId' => $this->resolveConversationId($supplier, $request),
        ]);

        return $productArray;
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
