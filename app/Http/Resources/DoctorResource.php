<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {


            return [
    'id'                 => $this->id,
    'is_verified'        => $this->is_verified,
    'phone'              => $this->phone,
    'profile_image_url'  => $this->profile_image_url,
    'created_at'         => $this->created_at,
    'updated_at'         => $this->updated_at,

    // Fields from the related 'allUser' (adjust relationship name if different)
    'fullname'           => $this->allUser->fullname ?? null,
    'email'              => $this->allUser->email ?? null,
    'status'             => $this->allUser->status ?? null,
    'address'             => $this->allUser->address ?? null,
    'email_verified_at'  => $this->allUser->email_verified_at ?? null,

    // Nested resources for relationships
    'doctor_license'     => new DoctorLicenseResource($this->doctorLicense),   // assuming relation is 'doctorLicense'
/*     'doctor_addresses'   => DoctorAddressResource::collection($this->doctorAddresses), // assuming relation is 'doctorAddresses'
 */


        ];
    }

}
