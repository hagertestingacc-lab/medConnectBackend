<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SupplierResource;
use App\Http\Resources\SupplierConversationResource;
use App\Http\Requests\Supplier\UpdateSupplierStatusRequest;
use App\Models\AllUserPart\AllUser;
use App\Models\SupplierPart\Supplier;
use App\Notifications\customNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SupplierController extends Controller
{
    public function getProfile(Request $request)
    {
        try {
            $supplier = $request->user()?->supplier()->first();

            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'error' => 'Supplier profile not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Supplier profile returned successfully',
                'data' => new SupplierResource($supplier)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured while fetching the profile, please try again ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(UpdateSupplierStatusRequest $request, $id)
    {
        //Validate the status from request
        $validated = $request->validated();
        try {

            //Search for supplier in AllUser table
            $user = AllUser::where("id", $id)
                ->where("role", "supplier")->first();

            if (!$user)
                return response()->json([
                    "success" => false,
                    'error' => 'Supplier not found'
                ], 404);


            //Update supplier status
            $user->update(["status" => $validated["status"]]);

            //Send notification to supplier by his status
            $status = $validated["status"];
            $fullname = $user["fullname"];
            $user->notifyNow(new customNotification("Hello Mr/Ms $fullname, Your are now $status in MedConnect "));

            //return success message if updated
            return response()->json([
                "success" => true,
                'message' => 'Supplier status updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return  response()->json([
                "success" => false,
                "message" => "An issue occured while updating, please try again" . $e->getMessage(),
            ], 500);
        }
    }


    //For Doctor side
    public function showByIDForDoctor(Request $request, $id)
    {
        try {

            $supplier = Supplier::find($id);
            if (!$supplier)
                return  response()->json([
                    "success" => false,
                    "error" => "Supplier Not found",

                ], 404);

            return  response()->json([
                "success" => true,
                "message" => "supplier returned successfully",
                "data" => (new SupplierConversationResource($supplier))->resolve()

            ], 200);
        } catch (\Exception $e) {

            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }


    //For supplier data for admin & supplier

    public function show(Request $request)
    {

        try {

            $suppliers = Supplier::get();
            if ($suppliers->isEmpty())
                return  response()->json([
                    "success" => false,
                    "error" => "Suppliers Not found",

                ], 404);

            return  response()->json([
                "success" => true,
                "message" => "suppliers returned successfully",
                "data" => SupplierConversationResource::collection($suppliers)->resolve()

            ], 200);
        } catch (\Exception $e) {

            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }

    //For supplier data for admin & supplier

    public function showById(Request $request, $id)
    {
        try {

            $supplier = Supplier::find($id);
            if (!$supplier)
                return  response()->json([
                    "success" => false,
                    "error" => "Supplier Not found",

                ], 404);

            return  response()->json([
                "success" => true,
                "message" => "supplier returned successfully",
                "data" => (new SupplierConversationResource($supplier))->resolve()

            ], 200);
        } catch (\Exception $e) {

            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }

}
