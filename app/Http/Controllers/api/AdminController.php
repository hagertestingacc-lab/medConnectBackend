<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\AllUserPart\AllUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */



public function register(Request $request )
{
    try
    {

    $validated = $request->validate([
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8',
        'address' => 'required|string',
    ]);

       AllUser::create([
        'fullname' => $validated['full_name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        "address"=> $validated['address'],
        "role"=>"admin",
        "status"=>"active",
        "email_verified_at"=>now()
    ]);

return [
     "success"=>true,
      "message"=>"Admin registered successfully",

];

    }
catch (\Exception $e) {
      return  response()->json([
                "success"=>false,
                "error"=>"An issue occured while registering, please try again".$e->getMessage(),

            ],500);


        }}

      public function login(Request $request)
    {
 $validated= $request->validate([
        "email"=>"required|email" , "password"=>"required",
        'role' => 'required|in:admin' ]);

    try{



    $user =AllUser::where('email', $validated['email'])
               ->where('role', $validated['role'])
               ->first();


        if (!$user || !Hash::check($validated['password'], $user->password))
            return  response()->json([
                "success"=>false,
                "error"=>"Incorrect credentials. Please double‑check and try again.",

            ],401);

    $token = $user->createToken('auth-token',['admin'],now()->addDays(30))->plainTextToken;


 return  response()->json([
                "success"=>true,
                "message"=>"Admin logged in successfully ",
                "user"=>$user,
"token"=>$token
            ],200);


    } catch (\Exception $e) {
      return  response()->json([
                "success"=>false,
                "error"=>"An issue occured while logging, please try again".$e->getMessage(),

            ],500);


        }
    }


}