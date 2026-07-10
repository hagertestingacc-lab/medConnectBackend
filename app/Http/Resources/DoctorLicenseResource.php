<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorLicenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray( $request): array
    {
        return [
            'license_number'  => $this->license_number,
            'specialty'  => $this->specialty,
/*             'issue_authority' => $this->issue_authority,
            'authority_type'  => $this->authority_type,
            'governorate'     => $this->governorate,
 */
/*    'city'            => $this->city,
 *//* *//*             'workplace'       => $this->workplace,
            'license_level'   => $this->license_level,
            'can_purchase'    => $this->can_purchase,
 */        ];
    }
}
