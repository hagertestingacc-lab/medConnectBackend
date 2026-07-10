<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\AllUserPart\AllUser;
use App\Models\DoctorPart\DoctorLicense;
use Carbon\Carbon;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class VerificationDoctorController extends Controller
{

//send  verfication
public function verify(Request $request ,$id ,$hash){

$doctorUser =AllUser::findOrFail($id);
$message='';
if( $this->hash_Not_equals($hash,$doctorUser)){
$message='Invalid verification link';
    return  view("verification.failed",compact('message'));
}

if($doctorUser->hasVerifiedEmail())
    {
    $message='Email already verified';

    return  view("verification.success",compact('message'));
    }

if($doctorUser->markEmailAsVerified())
event(new Verified($doctorUser));

$this->updateUserStatus($doctorUser);


    $message='Email verified successfully';
    return  view("verification.success",compact('message'));


}


//resend Expired verfication

public function resendExpired(Request $request ){

$request->validate(['email' => 'required|email:rfc,dns', 'password'=>'required']);

$doctor = AllUser::where("email",$request->email)->first();

if(!$doctor)
return response()->json(['success'=>false, 'message' => 'user not found'], 404);

if(!Hash::check($request->password,$doctor->password))
return response()->json(['success'=>false, 'message' => ' password is uncorrect'], 404);


if($doctor->hasVerifiedEmail() )
return response()->json(['success'=>true,'message' => 'Email already verified'], 400);



$doctor->sendEmailVerificationNotification();


return response()->json(['success'=>true,'message' => 'Verification link sent'],200);
}



//Check hashing
private function hash_Not_equals($hash,$doctorUser)
{
return !hash_equals((string)$hash,sha1($doctorUser->getEmailForVerification()));
}

//update user status
private function updateUserStatus($doctorUser) {

$doctor= $doctorUser->doctor;

// Check if doctor relationship exists
if(!$doctor) {
    Log::error('Doctor license is missing', ['user_id' => $doctorUser->id]);
    return;
}

$doctorLicense= $doctor->doctorLicense;

/*

if($doctorLicense->expiry_date->isPast() )
    {$doctorUser->status="suspended";}
    else{
        } */
        $doctorUser->status="active";
    $doctor->is_verified=true;
    $doctorUser->save();
    $doctor->save();
    }
    }
