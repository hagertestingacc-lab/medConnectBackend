<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\AllUserPart\AllUser;
use App\Notifications\otpNotification;
use Ichtrojan\Otp\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordsController extends Controller
{



public function forgetPassword(ForgotPasswordRequest $request)
{

$validate = $request->validated();

try{

$user = AllUser::where("email",$validate["email"])->first();
if(!$user)
   return response()->json([
        "success"=>false,
        "error"=>"User not found "
      ],404);

    $user->notify(new otpNotification());
     return response()->json([
        "success"=>true,
        "message"=>"An otp successfully sent",
      ],200);
}
catch (\Exception $e) {
      return  response()->json([
                "success"=>false,
                "error"=>"An issue occured , please try again ".$e->getMessage(),

            ],500);
}
}




public function verifyOtp(VerifyOtpRequest $request)
{

    $validate = $request->validated();

try{
$otp = new Otp();
$userOtp=$otp->validate($validate["email"],$validate["otp"]);
    if(!$userOtp->status)
         return response()->json([
        "success"=>false,
        "error"=>$userOtp->message
      ],401);

$user = AllUser::where("email",$validate["email"])->first();
if(!$user)
   return response()->json([
        "success"=>false,
        "error"=>"User not found "
      ],404);

$token = $user->createToken("auth-token" , ["resetPassword"] , now()->addMinutes(30));

        return response()->json([
        "success"=>true,
        "message"=>"An otp is matched",
        "token"=>$token->plainTextToken
      ],200);


}
catch (\Exception $e) {
      return  response()->json([
                "success"=>false,
                "error"=>"An issue occured , please try again".$e->getMessage(),

            ],500);
}

}

public function resetPasswords(ResetPasswordRequest $request)
{

    $validate = $request->validated();

try{
    $user =$request->user();

$token=$user->tokenCan("resetPassword");
if(!$token)
 return  response()->json([
                "success"=>false,
                "error"=>"  unallowed action, this for rest-password only",

            ],401);

$user->update(["password"=>Hash::make($validate["password"])]);


$request->user()->tokens()->delete();

 return  response()->json([
                "success"=>true,
                "message"=>"Password updated successfully",

            ],201);
}
catch (\Exception $e) {
      return  response()->json([
                "success"=>false,
                "error"=>"An issue occured , please try again".$e->getMessage(),

            ],500);
}

}
}
