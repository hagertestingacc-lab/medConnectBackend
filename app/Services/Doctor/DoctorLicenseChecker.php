<?php

namespace App\Services\Doctor;

use App\Models\DoctorPart\Doctor;
use App\Models\DoctorPart\DoctorLicense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DoctorLicenseChecker
{
    public  function check(string $license_number, string $national_id, string $full_name)
    {

    try {

        if(!$license_number)
         return $this->faildResponse('Please check your license details.',403);
        $license = DoctorLicense::where('license_number', $license_number)
            ->where('national_id', $national_id)
            ->where('full_name', $full_name)
            ->first();

        if ( !$license  ) {
     return $this->faildResponse('Registration not permitted. Please check your license details.',403);
        }



        if ( Doctor::where("license_table_id", $license?->license_number)->exists() ) {
     return $this->faildResponse('Registration not permitted. ',403);
        }


/*
     if ($license->expiry_date->isPast())
          return $this->successResponse("Your registration is active, but your license has expired. Please renew it soon to keep enjoying full access to our service",$license,200);
 */

        return $this->successResponse("Registration  permitted",$license,200);

    }
  catch (\Exception $e )
    {
         return $this->faildResponse('An error occurred during registration.'.$e->getMessage(),500);
    }
    }





    private function successResponse($message,$data,$statusCode)
{
        return[
    'success' => true,
    'message' => $message,
    "data"=>$data,
    'status' => $statusCode
];
}
private function faildResponse($message,$statusCode)
{
     return[
    'success' => false,
    'message' => $message,
    'status' => $statusCode
];
}

}
