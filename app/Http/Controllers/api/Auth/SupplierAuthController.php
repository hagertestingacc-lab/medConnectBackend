<?php

namespace App\Http\Controllers\api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\supplierDataRequest;
use App\Http\Resources\SupplierResource;
use App\Models\AllUserPart\AllUser;
use App\Models\SupplierPart\Supplier;
use App\Rules\Supplier\SupplierRegisteration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SupplierAuthController extends Controller
{

    public function register(supplierDataRequest $request)
    {
        try
        {
      $validated = $request->validated();

 $newSupplier = SupplierRegisteration::register($request,$validated);

return response()->json(
        [
       "success"=>$newSupplier["success"],
        "message"=>$newSupplier["message"]
      ],
       (int) $newSupplier["status"]
      );


        }
        catch(\Exception $e)
        {
      return response()->json(
        [
       "success"=>false,
        "error"=>"An issue occured while logging, please try again ".$e->getMessage()
      ],$e->getCode()?:500);

        }

    }






   public function login(Request $request)
    {
 $validated= $request->validate([
        "email"=>"required|email" , "password"=>"required",
        'role' => 'required|in:supplier' ]);

    try{



    $user =AllUser::where('email', $validated['email'])
               ->where('role', $validated['role'])
               ->first();


        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                "success"=>false,
                "error"=>"Incorrect credentials. Please double‑check and try again.",

            ],401);

        }

        if($user->status=="pending" )
             return response()->json([
                "success"=>false,
                "error"=>"An admin will review your request shortly – thank you for your patience!",

            ],401);

        if($user->status!="active")
            return response()->json([
                "success"=>false,
             "error"=>"You're currently unable to access your account .",
            ],401);




$supplierData =  Supplier::with([ 'allUser'])
                        ->where('user_table_id', $user->id)
                        ->first();


        if (!$supplierData)
      return  response()->json([
                "success"=>false,
             "error"=>"Supplier profile not found .",
            ],404);




   $SupplierResourceData =  new SupplierResource($supplierData);
    $token = $user->createToken('auth-token',['supplier'],now()->addDays(30))->plainTextToken;


 return response()->json([
                "success"=>true,
             "message"=>"Supplier logged in successfully ",
             'data'=> $SupplierResourceData,
             'token'=>$token
            ],200);
    } catch (\Exception $e) {

      return  response()->json([
                "success"=>false,
             "error"=>"An issue occured while logging, please try again",
            ],500);



        }
    }


}