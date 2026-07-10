<?php

namespace App\Http\Controllers\api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
  public function logout(Request $request)
    {

try{

$accessToken = $request->user()->currentAccessToken();
  
 $request->user()->tokens()->where('id', $accessToken->id)->delete();

    return  response()->json([
                "success"=>true,
             "message"=>"Logged out successfully",
            ],200); 
}
catch (\Exception $e) {
      return  response()->json([
                "success"=>false,
             "error"=>"An issue occured while logging out:".$e->getMessage(),
            ],500); 
      

}


    }



    }