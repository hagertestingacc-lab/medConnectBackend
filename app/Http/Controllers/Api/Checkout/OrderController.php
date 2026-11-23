<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AdminUpdateOrderStatusRequest;
use App\Http\Requests\Order\AssignOrderStatusRequest;
use App\Http\Requests\Order\CancelOrderItemsRequest;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\IndexOrdersRequest;
use App\Http\Requests\OrderIssueRequest;
use App\Http\Requests\Order\ReturnRentalProductsRequest;
use App\Http\Resources\OrderPaginatorResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ReturnedOrderItemResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Notifications\customNotification;


class OrderController extends Controller
{
    public function index(IndexOrdersRequest $request)
    {
        $request->validated();
        try {
            $doctor = $request->user()->doctor;
            $perPage = $request->query('per_page', 15);
            $type = $request["type"] ?? "sale"; // 'rental' or 'sale'

            $ordersQuery = $doctor->orders()
                ->with('items.product')
                ->orderByDesc('created_at');

            if ($type) {
                $ordersQuery->where('order_type', $type);
            }

            $orders = $ordersQuery->paginate($perPage);


            $orders->getCollection()->each(fn($order) => $order->items->makeHidden('sub_status'));
            if ($type == "rental") {
                $orders->load('items.product.rentalDetails');
                $orders->load('items.extendRent');
            }

            return response()->json([
                'success' => true,
                'data' => (new OrderPaginatorResource($orders))->resolve(),
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
        $order->load(["items.extendRent", 'items.product', 'doctor']);
        $order->items->makeHidden('sub_status');
        return response()->json([
            'success' => true,
            'data' => (new OrderResource($order))->resolve(),
        ]);
    }

    public function cancel(CancelOrderRequest $request, Order $order)
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
            'data' => (new OrderResource($order))->resolve(),
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
            'data' => (new OrderResource($order))->resolve(),
        ]);
    }

    public function supplierIndex(Request $request)
{
    $supplier = $request->user()->supplier;
    $perPage = $request->query('per_page', 15);

    $orders = Order::whereHas('items.product', function ($query) use ($supplier) {
            $query->where('supplier_id', $supplier->id);
        })
        ->with([
            'items' => function ($query) use ($supplier) {
                $query->whereHas('product', function ($q) use ($supplier) {
                    $q->where('supplier_id', $supplier->id);
                });
            },
            'items.product',
            'items.extendRent',
            'doctor',
            'doctor.allUser:id,email',
        ])
        ->orderByDesc('created_at')
        ->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => OrderResource::collection($orders->items())->resolve(),
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

    if (! $isRelated) {
        return response()->json([
            'success' => false,
            'error' => 'Unauthorized.',
        ], 403);
    }

    $order->load([
        'items' => function ($query) use ($supplier) {
            $query->whereHas('product', function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            });
        },
        'items.product',
        'items.extendRent',
        'doctor',
    ]);

    return response()->json([
        'success' => true,
        'data' => (new OrderResource($order))->resolve(),
    ]);
}

    // product for supplier
    public function returnRentalProducts(ReturnRentalProductsRequest $request, Order $order)
    {
        $supplier = $request->user()->supplier;

        $validated = $request->validated();

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
            'data' => (new ReturnedOrderItemResource([
                'order' => $order,
                'item' => $item,
                'rentalDetails' => $rentalDetails,
            ]))->resolve(),
        ]);
    }


    public function adminUpdateStatus(AdminUpdateOrderStatusRequest $request, Order $order)
    {
        $validated = $request->validated();

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

    public function assignStatus(AssignOrderStatusRequest $request, Order $order)
    {
        $validated = $request->validated();

        try {
            $supplier = $request->user()->supplier;

            $updated = $order->items()
                ->forSupplier($supplier->id)
                ->whereIn('sub_status', ["paid", "confirmed", "ready", "processing", "partial_ready", "partial_processing"])->update(['sub_status' => $validated['status']]);

            if ($updated === 0) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No updatable items found for this supplier on this order',
                ], 404);
            }

            $changed = $order->refreshStatus();

            if ($changed) {
                $order->doctor->allUser->notifyNow(
                    new customNotification("Your order {$order->order_number} is now {$order->status}")
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'order updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'An issue occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancelItems(CancelOrderItemsRequest $request, Order $order)
    {
        $validated = $request->validated();

        try {
            $supplier = $request->user()->supplier;

            $items = $order->items()
                ->forSupplier($supplier->id)
                ->whereIn('sub_status', ["paid", "confirmed", "ready", "processing", "partial_ready", "partial_processing"])
                ->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No cancellable items found for this supplier on this order',
                ], 404);
            }

            DB::transaction(function () use ($items, $validated) {
                foreach ($items as $item) {
                    $item->update([
                        'sub_status'    => 'cancelled',
                        'cancel_reason' => $validated['reason'],
                        'cancelled_at'  => now(),
                    ]);
                }
            });

            $order->refreshStatus();

            $order->doctor->allUser->notifyNow(
                new customNotification(
                    "Part of your order {$order->order_number} was cancelled by a supplier. " .
                        "Reason: {$validated['reason']}. Order status: {$order->status}"
                )
            );

            return response()->json([
                'success'      => true,
                'message'      => 'items cancelled successfully',
                'order_status' => $order->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'An issue occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

}
