<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestockNotificationRequest;
use App\Models\ProductPart\Product;
use App\Models\RestockNotification;
use App\Notifications\customNotification;
use Illuminate\Http\Request;

class RestockNotificationController extends Controller
{
    public function store(Request $request,Product $product)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        if($product->stock!==0 )
            return response()->json([
            'success' => false,
            'error' => 'The product already have a stock.',
        ],422);
        if($product->restock_date==null )
            return response()->json([
            'success' => false,
            'error' => 'The product restock date not ava.',
        ],422);

        if($product->restockNotifications()->where('doctor_id', $doctor->id)->exists())
         return response()->json([
            'success' => false,
            'error' => 'You are already on list.',
        ],422);

        $notification = $product->restockNotifications()->firstOrCreate([
            'doctor_id' => $doctor->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Restock notification created.',
            'data' => $notification,
        ]);
    }

    public function notify(Request $request, Product $product)
    {
        $notifications = RestockNotification::where('product_id', $product->id)->get();

        foreach ($notifications as $notification) {
            $notification->doctor->notifyNow(new customNotification("Product {$product->name} has been restocked."));
        }

        RestockNotification::where('product_id', $product->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifications sent and records deleted.',
        ]);
    }

    public function undo(Request $request, Product $product)
    {
                $doctor = $request->user()->doctor;
    $restockNotification= $product->restockNotifications()->where("doctor_id",$doctor->id)->first();

        if (!$restockNotification) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }


        $restockNotification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Restock notification removed.',
        ]);
    }

    public function isNotify(Request $request, Product $product)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        $exists = $product->restockNotifications()->where('doctor_id', $doctor->id)->exists();

        return response()->json([
            'success' => true,
            'isNotified' => $exists,
        ]);
    }
}
