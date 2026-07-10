<?php

namespace App\Http\Controllers\api\customRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\offerRequestRequest;
use App\Http\Requests\showParams;
use App\Models\customRequestsPart\customRequest;
use App\Models\customRequestsPart\OfferRequest;
use App\Notifications\customNotification;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class OfferRequestController extends Controller
{
    use AuthorizesRequests;

    //create offer side
    public function create(offerRequestRequest $request, customRequest  $customRequest)
    {
        try {
            $validated = $request->validated();
            $supplier = $request->user()->supplier;

            $exists = $supplier->offerRequest()
                ->where('request_id', $customRequest->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'error' => 'You already have an offer for this request.',
                ], 422);
            }

            $validated["request_id"] = $customRequest->id;
            $offer =  $supplier->offerRequest()->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Offer request created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }


    //table offer side
    public function showForSupplier(Request $request, showParams $params)
    {
        try {
            $supplier = $request->user()->supplier;
            $query = $supplier->offerRequest();

            $per_page = $params->query('per_page', 15);
            $filter_by = $params->query('filter_by');
            $filter_value = $params->query('filter_value');
            $sort_by = $params->query('sort_by', 'id');
            $sort_order = $params->query('sort_order', 'asc');


            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }

            $offers = $query->orderBy($sort_by, $sort_order)->orderByDesc("created_at")
                ->paginate($per_page);


            return response()->json([
                'success' => true,
                'message' => 'Offer requests returned successfully.',
                'data' => $offers->items(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }
    //table order requests
    public function showOrder(Request $request, showParams $params)
    {
        try {
            $supplier = $request->user()->supplier;
            $query = $supplier->offerRequest();

            $per_page = $params->query('per_page', 15);
            $filter_by = $params->query('filter_by');
            $filter_value = $params->query('filter_value');
            $sort_by = $params->query('sort_by', 'id');
            $sort_order = $params->query('sort_order', 'asc');


            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }



            $offers = $query->where("status", "accepted")->with(
                "customRequest:id,status,doctor_id",          // ← includes foreign key to doctor
                "customRequest.doctor:id,user_table_id",            // ← includes foreign key to user
                "customRequest.doctor.allUser:id,email,fullname"
            )->orderBy($sort_by, $sort_order)->orderByDesc("created_at")
                ->paginate($per_page, ["id", "status", "request_id", "supplier_id"]);

            return response()->json([
                'success' => true,
                'message' => 'order requests returned successfully.',
                'data' => $offers->items(),
                'last_page' => $offers->lastPage(),
                'per_page' => $offers->perPage(),
                'total' => $offers->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }

    public function assignStatus(Request $request, OfferRequest $offerRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:in negotiation,shipped,delivered'
        ], [
            'status.in' => 'Status must not be open and must be one of: in negotiation, shipped, delivered.',
        ]);

        try {
            $this->authorize('offerSupplier', $offerRequest);

            if ($offerRequest->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only accepted offer orders can have status assigned by the supplier.',
                ], 422);
            }

            $customRequest = $offerRequest->customRequest;
            if (!$customRequest) {
                return response()->json([
                    'success' => false,
                    'error' => 'Linked custom request not found.',
                ], 404);
            }

            $customRequest->update(['status' => $validated['status']]);
            $user=$customRequest->doctor->allUser;
            $user->notifyNow( new customNotification("Your CustomRequest order  {$customRequest->id} is now {$offerRequest->status}")) ;

            return response()->json([
                'success' => true,
                'message' => 'Order status assigned successfully.',
                'data' => $customRequest,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }

    //table order requests by id

    public function showOrderById(offerRequest $offerRequest)
    {
        try {
            $this->authorize("offerSupplier", $offerRequest);


            $offers = $offerRequest->with(["customRequest", "customRequest.doctor:id,user_table_id", "customRequest.doctor.allUser:id,email,fullname,address"])->get();

            return response()->json([
                'success' => true,
                'message' => 'order requests returned successfully.',
                'data' => $offers,

            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }


    // Doctor side - offers of request
    public function showByIdForDoctor(Request $request, customRequest $customRequest)
    {
        try {
            $this->authorize("requestDoctor", $customRequest);

            $offers = $customRequest->offerRequest()->with('supplier:id,company_name,company_image_url')->get();
            return response()->json([
                'success' => true,
                'message' => 'Offer requests returned successfully.',
                'data' => $offers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }
    public function offerResponseForDoctor(request $request, offerRequest $offerRequest)
    {
        $validated = $request->validate([
            'response' => "required|in:rejected,accepted"
        ], [
            "response.in:should be rejected or accepted"
        ]);
        try {

            $this->authorize("offerResponceDoctor", $offerRequest);
            $customRequest = $offerRequest->customRequest;


            if ($customRequest->status !== "open")
                return response()->json([
                    'success' => false,
                    'error' => 'The request already in ' . $customRequest->status
                ], 422);

            $offerRequest->update(["status" => $validated["response"]]);

            if ($validated["response"] == 'accepted') {
                $offerRequest->customRequest()->update(["status" => "in negotiation"]);
                $id = $offerRequest->customRequest->id;
                OfferRequest::where("request_id", $id)->where("id", "!=", $offerRequest->id)
                    ->update(["status" => "rejected"]);

            }
            $user=$offerRequest->supplier->allUser;
            $user->notifyNow( new customNotification("Your offerRequest to   {$customRequest->id} is now {$offerRequest->status}")) ;



            return response()->json([
                'success' => true,
                'message' => 'Offer requests ' . $validated["response"] . ' successfully.',

            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }
}
