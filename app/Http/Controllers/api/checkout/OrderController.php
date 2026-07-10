<?php

namespace App\Http\Controllers\api\checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderIssueRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Notifications\customNotification;


class OrderController extends Controller
{
    public function index(Request $request)
    {
      $request->validate([
            "type" => "required|in:sale,rental"
        ], [
            "message" => "type must be sale or rental , by default is sale"
        ]);
        try {
            $doctor = $request->user()->doctor;
            $perPage = $request->query('per_page', 15);
            $type = $request["type"]??"sale"; // 'rental' or 'sale'

            $ordersQuery = $doctor->orders()
                ->with('items.product')
                ->orderByDesc('created_at');

            if ($type) {
                $ordersQuery->where('order_type', $type);
            }

            $orders = $ordersQuery->paginate($perPage);


            $orders->getCollection()->each(fn($order) => $order->items->makeHidden('sub_status'));
            if($type=="rental"){
            $orders->load('items.product.rentalDetails');
            $orders->load('items.extendRent');

            }

            return response()->json([
                'success' => true,
                'data' => $orders,
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Order $order)
    {
        $doctor = $request->user()->doctor;

        if ($order->doctor_id !== $doctor->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }
        $order->load(["items.extendRent",'items.product', 'doctor']);
        $order->items->makeHidden('sub_status');
        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /*   public function store(OrderCreateRequest $request)
    {
        try {
            $doctor = $request->user()->doctor;
            $validated = $request->validated();

        if($validated['order_type'] === 'sale')
            $order= Order::makeCartOrder($doctor,$validated);

/*
         if ($validated['order_type'] === 'rental' && (empty($item['rental_start']) || empty($item['rental_end']))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rental items must include rental_start and rental_end.',
                    ], 422);
                } */



    /*       return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => $order,
            ], 201);
        }
         catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occurred: ' . $e->getMessage(),
            ], 500);
        }
    }  */

    public function cancel(Request $request, Order $order)
    {
        $doctor = $request->user()->doctor;

        if ($order->doctor_id !== $doctor->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }
        if (!($order->cancelIfPending()  ||  $order->cancelIfConfirmed())) {
            return response()->json([
                'success' => false,
                'error' => 'Order can only be cancelled when it is pending or confirmed.',
            ], 422);
        }

        

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data' => $order,
        ]);
    }


    public function assignIssue(OrderIssueRequest $request, Order $order)
    {
        $doctor = $request->user()->doctor;

        if ($order->doctor_id !== $doctor->id) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $status = $order->status;

        if ($status == "cancelled") {
            return response()->json([
                'success' => false,
                'error' => 'Order can not be issued in  cancelled status .',
            ], 422);
        }
        $order->assignIssue($request->validated()['order_issue']);

        return response()->json([
            'success' => true,
            'message' => 'Issue assigned successfully.',
            'data' => $order,
        ]);
    }

    public function supplierIndex(Request $request)
    {
        $supplier = $request->user()->supplier;
        $perPage = $request->query('per_page', 15);

        $orders = Order::whereHas('items.product', function ($query) use ($supplier) {

            $query->where('supplier_id', $supplier->id);
        })
            ->with(['items.product',"items.extendRent", 'doctor', 'doctor.allUser:id,email'])->orderByDesc('created_at')
            ->paginate($perPage);



        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ]);
    }

    public function supplierShow(Request $request, Order $order)
    {
        $supplier = $request->user()->supplier;

        $isRelated = $order->items()->whereHas('product', function ($query) use ($supplier) {
            $query->where('supplier_id', $supplier->id);
        })->exists();

            $order->load('items.extendRent');


        if (! $isRelated) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order->load(['items.product', 'doctor']),
        ]);
    }

    // product for supplier
    public function returnRentalProducts(Request $request, Order $order)
    {
        $supplier = $request->user()->supplier;

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:product,id',
        ]);

        if ($order->status === 'returned') {
            return response()->json([
                'success' => false,
                'error' => 'This order returned already.',
            ], 422);
        }
        if ($order->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'error' => 'Rental products can only be returned after the order is delivered.',
            ], 422);
        }

        $item = $order->items()
            ->where('product_id', $validated['product_id'])
            ->whereNotNull('rental_end')
            ->whereHas('product', function ($query) use ($supplier) {
                $query->where('supplier_id', $supplier->id)
                    ->whereHas('rentalDetails');
            })
            ->with('product.rentalDetails')
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'error' => 'This rental product was not found in the order or does not belong to your supplier account.',
            ], 422);
        }
        if ($item->sub_status == 'returned') {
            return response()->json([
                'success' => false,
                'error' => 'This rental product is returned already.',
            ], 422);
        }



        $rentalDetails = $item->product->rentalDetails;
        if (! $rentalDetails) {
            return response()->json([
                'success' => false,
                'error' => 'Rental details are missing for this product.',
            ], 422);
        }

        $newAvailable = $rentalDetails->available_units + $item->quantity;
        if ($newAvailable > $rentalDetails->stock_units) {
            return response()->json([
                'success' => false,
                'error' => 'Returned quantity exceeds the rental stock limit for this product.',
            ], 422);
        }

        DB::transaction(function () use ($rentalDetails, $item, $order) {
            $rentalDetails->available_units += $item->quantity;
            $rentalDetails->save();

            $item->sub_status = 'returned';
            $item->save();

            $allReturned = $order->items()->where('sub_status', '!=', 'returned')->doesntExist();

            if ($allReturned) {
                $order->status = 'returned';
                $order->save();
            }
        });

        $rentalDetails->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Rental product returned successfully.',
            'data' => [
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'available_units' => $rentalDetails->available_units,
                'stock_units' => $rentalDetails->stock_units,
            ],
        ]);
    }


    public function adminUpdateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,paid,processing,ready,shipped,delivered,cancelled,returned',
            'sub_status' => 'required|in:pending,confirmed,paid,processing,ready,shipped,delivered,cancelled,returned,unReturned',
        ], [
            'status.in' => 'The order status is not valid.',
            'sub_status.in' => 'The item sub-status is not valid.',
        ]);

        try {
            DB::transaction(function () use ($order, $validated) {
                $order->status = $validated['status'];
                $order->save();

                $order->items()->update([
                    'sub_status' => $validated['sub_status'],
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'status' => $validated['status'],
                    'sub_status' => $validated['sub_status'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function assignStatus(Request $request, Order $order)
    {
        $validated =  $request->validate(
            [
                "status" => "required|in:processing,ready"
            ],
            [
                "status.in" => "you can only assign to processing or ready"
            ]
        );
        try {

            $user = $request->user()->supplier;



            $order->items()->ForSupplier($user->id)
                ->update(["sub_status" => $validated["status"]]);

            $statuses = $order->items->pluck("sub_status");

            $order->status = match (true) {
                $statuses->contains("processing") => "processing",
                $statuses->every(fn($s) => $s === 'ready') => "ready",
                default                => $order->status
            };



            if ($order->isDirty('status')) {
                $order->save();

                $order->doctor->allUser->notifyNow(new customNotification("Your order  {$order->order_number} is now {$order->status}"));
            }

            return response()->json(
                [
                    "success" => true,
                    "message" => "order upated successfully"
                ]
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}