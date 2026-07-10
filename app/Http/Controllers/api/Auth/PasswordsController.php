<?php

namespace App\Http\Controllers\api\Auth;

use App\Http\Controllers\Controller;
use App\Models\AllUserPart\AllUser;
use App\Notifications\otpNotification;
use Ichtrojan\Otp\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordsController extends Controller
{



public function forgetPassword(Request $request)
{

$validate = $request->validate([
    "email"=>"required|email|string|min:10"
]);

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




public function verifyOtp(Request $request)
{

    $validate = $request->validate([
        "email"=>"required|email|min:10",
    "otp"=>"required|digits:4|numeric"
]);

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

public function resetPasswords(Request $request)
{

    $validate = $request->validate([
   'password' => ['required','string','confirmed','min:8','regex:/^(?=.*[A-Z])(?=.*\d)(?=.*\W).{8,}$/'],
],
['password.regex' => 'Password must contain at least 1 uppercase, 1 number, 1 symbol, and be 8+ characters.']);

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
