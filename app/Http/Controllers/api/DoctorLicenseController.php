<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\DoctorPart\DoctorLicense;
use Illuminate\Http\Request;

class DoctorLicenseController extends Controller
{
    public function getLicenses(Request $request ,$page, $per_page)
{
    try
    {

      $license = DoctorLicense::paginate($per_page,["*"],"page",$page);
      
    
      

      return response()->json([
        "success"=>true,
        "message"=>"license returned successfully",
        "data"=>$license->items(),
        "pagination" => [
        "current_page" => $license->currentPage(),
        "last_page" => $license->lastPage(),
        "per_page" => $license->perPage(),
        "total" => $license->total(),
    ]      ],200);
        
    }
    catch(\Exception $e){
          return response()->json([
        "success"=>false,
        "error"=>"An issue occured ".$e->getMessage(),
      ],500);
    }
    
}
}