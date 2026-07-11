<?php

namespace App\Http\Controllers\api\checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderCreateRequest;
use App\Models\ExtendRent;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //

    public function excutePayment(OrderCreateRequest $request)
    {
        $user = $request->user();

        $doctor = $request->user()->doctor;
        $validated = $request->validated();

        if ($validated["order_type"] == "sale") {

            $items = $doctor->cart;
            if ($items->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => 'Your cart is empty',
                ], 422);
            }

            $order = Order::makeCartOrder($doctor, $validated);
        }

        if ($validated["order_type"] == "rental")
            $order = Order::makeRentalOrder($doctor, $validated);

        if (!$order["success"])
            return response()->json($order);


        if ($validated["payment_type"] == "cash")
            return response()->json([
                "success" => true,
                "message" => 'order created successfully',
                "data" => $order
            ], 201);

        $days = $order["days"] ?? 1;
        $order = $order["data"];
        $cartItems = $order->items->map(
            fn($item) => [
                "name" => $item["product"]["name"],
                "price" => $item["unit_price"] * $days,
                "quantity" => $item["quantity"]
            ]
        );
        $curl = curl_init();

        // Prepare the POST data as an associative array (easier to maintain)
        $postData = [
            "payment_method_id" => 2,
            "cartTotal" => $order->total,
            "currency" => "EGP",
            "invoice_number" => $order->invoice_number,
            "customer" => [
                "first_name" => $user->fullname,
                "email" => $user->email,
                "phone" =>  $user->doctor->phone,
                "address" => $user->address
            ],
            "redirectionUrls" => [
                "successUrl" => "https://medconnect-one-pi.vercel.app/api/api/payment/success",
                "failUrl" => "https://medconnect-one-pi.vercel.app/api/api/payment/failed",
                "pendingUrl" => "https://medconnect-one-pi.vercel.app/api/api/payment/success"
            ],
            "cartItems" => $cartItems
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://staging.fawaterk.com/api/v2/invoiceInitPay',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30, // reasonable timeout instead of 0 (unlimited)
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postData), // properly encode JSON
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . env('FWATERK_API_KEY')
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        // Handle cURL transport error
        if ($response === false) {
            return response()->json([
                'success' => false,
                'error' => 'cURL Error: ' . $curlError
            ], 500);
        }

        // Decode the JSON response from Fawaterk
        $decodedResponse = json_decode($response, true);
        if (isset($decodedResponse['data']['invoice_key'])) {
            // Save Fawaterk's key so we can match it in the webhook later
            $order->update(['invoice_key' => $decodedResponse['data']['invoice_key']]);
        }

        // Handle malformed JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid JSON response from payment gateway',
                'raw_response' => $response
            ], 500);
        }

        // Return the original API response with its HTTP status code
        return response()->json($decodedResponse, $httpCode);
    }

    public function excuteExtendRentalPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'extension_days' => 'required|integer|min:1'
        ]);

        $user = $request->user();
        $order = Order::find($validated['order_id']);

        // Verify order belongs to user
        if ($order->doctor_id !== $user->doctor->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 403);
        }

        // Validate extension
        $extensionData = $order->extendRentalDays($validated);
        if (!$extensionData['success']) {
            return response()->json($extensionData, 422);
        }

        $item = $extensionData['item'];
        $cartItems = [[
            "name" => $item->product->name . " - Extension",
            "price" => $extensionData['extension_price'],
            "quantity" => 1
        ]];

        $curl = curl_init();

        $postData = [
            "payment_method_id" => 2,
            "cartTotal" => $extensionData['extension_price'],
            "currency" => "EGP",
            "invoice_number" => $order->invoice_number . "-EXT",
            "customer" => [
                "first_name" => $user->fullname,
                "email" => $user->email,
                "phone" => $user->doctor->phone,
                "address" => $user->address
            ],
            "redirectionUrls" => [
                "successUrl" => "https://medconnect-one-pi.vercel.app/api/api/payment/success",
                "failUrl" => "https://medconnect-one-pi.vercel.app/api/api/payment/failed",
                "pendingUrl" => "https://medconnect-one-pi.vercel.app/api/api/payment/success"
            ],
            "cartItems" => $cartItems
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://staging.fawaterk.com/api/v2/invoiceInitPay',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . env('FWATERK_API_KEY')
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        // Handle cURL transport error
        if ($response === false) {
            return response()->json([
                'success' => false,
                'error' => 'cURL Error: ' . $curlError
            ], 500);
        }

        // Decode the JSON response from Fawaterk
        $decodedResponse = json_decode($response, true);

        // Handle malformed JSON (moved BEFORE we try to use $decodedResponse)
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid JSON response from payment gateway',
                'raw_response' => $response
            ], 500);
        }

        if (isset($decodedResponse['data']['invoice_key'])) {
            $item->extendRent()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'invoice_key'    => $decodedResponse['data']['invoice_key'],
                    'extend_day'     => $extensionData['new_rental_end'],
                    'amount'         => $extensionData['extension_price'],
                    'payment_method' => 'online',
                ]
            );
        }
        return response()->json($decodedResponse, $httpCode);
    }




    public function getMethods()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://staging.fawaterk.com/api/v2/getPaymentmethods',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . env('FWATERK_API_KEY'),
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            return response()->json(['error' => 'cURL error: ' . $error], 500);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON response'], 500);
        }

        return response()->json($data, $httpCode);
    }


   public function webhook(Request $request)
{
    $validated = $request->validate([
        "invoice_key" => "required",
        "invoice_status" => "required"
    ]);

    try {
        Log::info('Fawaterk webhook received', $request->all());

        Order::cancelExpiredPendingOrders();

      $status = match ($validated["invoice_status"]) {
            "paid" => "paid",
            "failed" => "cancelled",
            default => "pending"
        };
        // Try extendRent payment first
        $extendRent = ExtendRent::where('invoice_key', $validated["invoice_key"])->first();

        if ($extendRent) {
            return $this->handlePaymentUpdate($extendRent, $status, function () use ($extendRent, $status) {
                if ($status === 'paid') {
                    $extendRent->item->order->confirmExtension($extendRent->item_id, $extendRent);
                }
            });
        } 

        // Fall back to a regular order payment
        $order = Order::where('invoice_key', $validated["invoice_key"])->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return $this->handlePaymentUpdate($order, $status);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'An issue occured ' . $e->getMessage(),
        ], 500);
    }
}

private function handlePaymentUpdate($model, string $status, ?callable $onPaid = null)
{
    $model->update(['status' => $status]);

    if ($status === 'paid' && $onPaid) {
        $onPaid();
    }

    return response()->json(['success' => true, 'data' => $model]);
}
    /*   public function webhook(Request $request)
    {
        $validated = $request->validate([
            "invoice_key" => "required",
            "invoice_status" => "required"
        ]);
        try {
            log::info('Fawaterk webhook received', $request->all());

            $order = Order::where("invoice_key", $validated["invoice_key"])->first();
            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }
            $status = match ($validated["invoice_status"]) {
                "paid" => "paid",
                "failed" => "cancelled",
                default => "pending"
            };

            // Check if this is an extension payment
            if ($order->order_issue && str_starts_with($order->order_issue, 'extension:')) {
                if ($status === 'paid') {
                    $extensionMeta = json_decode(substr($order->order_issue, 10), true);
                    $order->confirmExtension($extensionMeta['item_id'], $extensionMeta['extension_days']);
                }
            } else {
                $order->update(["status" => $status]);
            }

            return response()->json(['success' => true, "data" => $order]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    } */
}