<?php

namespace App\Http\Controllers\api\customRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\customRequestRequest;
use App\Http\Requests\showParams;
use App\Models\customRequestsPart\customRequest;
use App\Models\DoctorPart\Doctor;
use Illuminate\Http\Request;

class customRequestController extends Controller
{

    //Doctor access
    public function create(customRequestRequest $request)
    {
        try {
            $validated = $request->validated();
            $doctor = $request->user()->doctor;
            if ($validated["type"] !== "rental" && (isset($validated["rent_start_date"]) ||  isset($validated["rent_end_date"]))) {
                return response()->json([
                    "success" => false,
                    "error" => "You sent rental data but this is no rental type "
                ], 403);
            }

            $doctor->customRequest()->create($validated);

            return response()->json([
                "success" => true,
                "message" => "custom request created successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }
    public function showBydoctor(Request $request, showParams $params)
    {
        $per_page = $params->query("per_page", 15);
        $status = $params->query("status", 'all');
        try {
            $cutomRequest = $request->user()->doctor->customRequest()
                ->status($status)
                ->orderByDesc("created_at")
                ->withCount('offerRequest')
                ->paginate($per_page);

            return response()->json([
                "success" => true,
                "message" => "custom requests returned successfully",
                "data" => $cutomRequest->items(),
                "last_page" => $cutomRequest->lastPage(),
                "per_page" => $cutomRequest->perPage(),
                "total" => $cutomRequest->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }

    //supplier side , all doctors' open requests
    public function showForSupplier(Request $request, showParams $params)
    {
        $supplier = $request->user()->supplier;
        $per_page = $params->query("per_page", 15);

        try {
            $cutomRequest = customRequest::where("status", "open")
                ->whereDoesntHave('offerRequest', function ($query) use ($supplier) {
                    $query->where('supplier_id', $supplier->id);
                })
                ->paginate($per_page);

            return response()->json([
                "success" => true,
                "message" => "custom requests returned successfully",
                "data" => $cutomRequest->items(),
                "last_page" => $cutomRequest->lastPage(),
                "per_page" => $cutomRequest->perPage(),
                "total" => $cutomRequest->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }

    //admin side , all doctors' requests
    public function showAll(Request $request, showParams $params)
    {
        $per_page = $params->query("per_page", 15);
        $status = $params->query("status", "all");
        try {
            $cutomRequest = customRequest::status($status)->paginate($per_page);

            return response()->json([
                "success" => true,
                "message" => "custom requests returned successfully",
                "data" => $cutomRequest->items(),
                "last_page" => $cutomRequest->lastPage(),
                "per_page" => $cutomRequest->perPage(),
                "total" => $cutomRequest->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }
    public function cancel(Request $request, customRequest $customRequest)
    {

        try {
            if ($customRequest->doctor_id != $request->user()->doctor->id)
                return response()->json([
                    "success" => true,
                    "error" => "Unauthenticated",
                ], 401);

            $status = ["open", "in negotiation"];
            if ($customRequest->status == "cancelled")
                return response()->json([
                    "success" => true,
                    "message" => "Already cancelled",
                ], 403);

            $status = ["open", "in negotiation"];
            if (!\in_array($customRequest->status, $status))
                return response()->json([
                    "success" => true,
                    "error" => "You can cancel it in open or pending requests only, current status of request is $customRequest->status",
                ], 403);

            $customRequest->update(["status" => "cancelled"]);

            return response()->json([
                "success" => true,
                "message" => "custom requests cancelled ",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }
    public function delete(Request $request, customRequest $customRequest)
    {

        try {
            if ($customRequest->doctor_id != $request->user()->doctor->id)
                return response()->json([
                    "success" => true,
                    "error" => "Unauthenticated",
                ], 403);


            $status = ["expired", "cancelled"];
            if (!\in_array($customRequest->status, $status))
                return response()->json([
                    "success" => true,
                    "error" => "You can delete it in cancelled or expired requests only",
                ], 401);

            $customRequest->delete();

            return response()->json([
                "success" => true,
                "message" => "custom requests deleted successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }
}
