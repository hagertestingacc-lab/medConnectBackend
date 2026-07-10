<?php

namespace App\Http\Controllers\api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\doctorDataRequest;
use App\Http\Resources\DoctorResource;
use App\Models\AllUserPart\AllUser;
use App\Models\DoctorPart\Doctor;
use App\Responces\DoctorResponces;
use App\Rules\Doctor\DoctorRegisteration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DoctorAuthController extends Controller
{
      public function register(doctorDataRequest $request)
    {
        $response = new DoctorResponces();

        try {
            // Validation passed automatically if we reach here
            $validated = $request->validated();
            $newDoctor = new DoctorRegisteration();
            $newDoctorData = $newDoctor->register($validated, $request);



                 event(new Registered( $newDoctorData["data"]["allUser"]));

            return $response->successResponseMessage('Doctor registered successfully, Please verify your email.',
             201);

        } catch (\Exception $e) {
            return $response->errorResponseMessage($e->getMessage()
            , $e->getCode()?? 500);

        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function login(Request $request)
    {
        $response = new DoctorResponces();
 $validated= $request->validate([
        "email"=>"required|email" , "password"=>"required",
        'role' => 'required|in:doctor' ]);

    try{

    $user =AllUser::where('email', $validated['email'])
               ->where('role', $validated['role'])
               ->first();


        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $response->errorResponseMessage("Incorrect credentials. Please double‑check and try again."
            , 401);
        }

        if($user->status=="pending")
             return $response->errorResponseMessage("Please verify your account first "
            , 401);

               if($user->status!="active")
            return response()->json([
                "success"=>false,
             "message"=>"you are stop from using your account currently  .",
            ],401);



$doctorData =  Doctor::with(['doctorLicense', 'allUser'])
                        ->where('user_table_id', $user->id)
                        ->first();


        if (!$doctorData)
      return $response->errorResponseMessage('Doctor profile not found'   , 404);




    $DoctorResourceData= new DoctorResource($doctorData);
    $token = $user->createToken('auth-token',['doctor'])->plainTextToken;


 return $response->successResponseFullData("Doctor logged in successfully ",
            $DoctorResourceData,
            $token,
                    200);

    } catch (\Exception $e) {
      return $response->errorResponseMessage("An issue occured while logging, please try again".$e->getMessage() , 500);


        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {


        return response()->json(["message"=>"hello",
        "auth"=> auth('sanctum')->check(),
        "user"=>auth('sanctum')->user(),
        'header'=>$request->header('Authorization')

        ]);
    }

    /**
     * Update the specified resource in storage.
     */



}
