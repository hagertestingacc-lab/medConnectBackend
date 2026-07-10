<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
     'id'                 => $this->id,
    'is_verified'        => $this->is_verified,
    'phone'              => $this->phone,
    'created_at'         => $this->created_at,
    'updated_at'         => $this->updated_at,
    'fullname'           => $this->allUser->fullname ?? null,
    'email'              => $this->allUser->email ?? null,
    'status'             => $this->allUser->status ?? null,
    'address'            => $this->allUser->address ?? null,
    'national_id'        => $this->national_id ?? null,
    'company_name'       => $this->company_name ?? null,
    'governorate'        => $this->governorate ?? null,
    'certificate_name'   => $this->certificate_name ?? null,
    'certificate_image'  => $this->certificate_image ?? null,
    'company_image_url'  => $this->company_image_url ?? null,
    'tax_card_image'     => $this->tax_card_image ?? null,


        ] ;
    }
}