<?php

namespace App\Http\Controllers\api\checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\cartItemRequest;
use App\Http\Requests\cartQuantityRequest;
use App\Http\Requests\cartRentalDateRequest;
use App\Models\Cart;
use App\Models\ProductPart\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{

    public function validateRent(cartRentalDateRequest $request, Product $product)
    {

        try {
            $validated = $request->validated();
            $validateDate = $product->validateRent($validated);

            if ($validateDate !== null) {
                return response()->json(["success" => false, "error" => $validateDate], 422);
            }

            return response()->json(["success" => true, "sccuess" => "Rent is validated"], 200);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "error" => "an issue occured" . $e->getMessage()
                ],
                500
            );
        }
    }
    public function show(Request $request)
    {
        try {
            $doctor = $request->user()->doctor;
            $perPage = $request->query('per_page', 15);


            $deletedItems =  $doctor->cart()->whereHas("product", function ($q) {
                $q->where("is_archive", true)->orwhere("stock", "<=", 0);
            })->get();
            $deletedNames = $deletedItems->pluck("product.name")->join(", ");



            $doctor->cart()->whereIn("id", $deletedItems->pluck("id"))->delete();

            $cartItems = $doctor->cart()->with('product',"product.image")
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Cart items returned successfully.',
                "note" => $deletedNames ? 'The' . $deletedNames . ' no longer available and has been removed from your cart' : null,
                'data' => $cartItems->items(),
                'last_page' => $cartItems->lastPage(),
                'per_page' => $cartItems->perPage(),
                'totalPrice' => $cartItems->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }





    public function addItem(cartItemRequest $request, product $product)
    {
        try {
            $validated = $request->validated();
            $doctor = $request->user()->doctor;


            if ($validated['type'] !== 'sale')
                return response()->json([
                    'success' => false,
                    'error' => "It's only sale type ",
                ], 422);

            if (
                !($product->status == 'create_accepted'
                    || $product->status == 'edit_accepted')
                || $product->is_archive == true
            )
                return response()->json([
                    'success' => false,
                    'error' => "Product not found",
                ], 404);




            $existing = Cart::where('doctor_id', $doctor->id)
                ->where('product_id', $product->id)
                ->where('type', 'sale')
                ->first();
            //IF EXISTED
            if ($existing) {
                if ($product->stock < ($validated['quantity'] + $existing->quantity)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Insufficient stock for purchase.',
                    ], 422);
                }
                $existing->increment('quantity', $validated['quantity']);

                return response()->json([
                    'success' => true,
                    'message' => 'quantity updated to ' . $existing->quantity . ' successfully.',
                ], 200);
            }
            //IF EXISTED




            if ($product->stock < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient stock for purchase.',
                ], 422);
            }

            Cart::create([
                'doctor_id' => $doctor->id,
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'type' => 'sale',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateQuantity(cartQuantityRequest $request, Cart $cart)
    {
        try {
            $doctor = $request->user()->doctor;

            if ($cart->doctor_id !== $doctor->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized action.',
                ], 403);
            }

            $validated = $request->validated();
            $product = Product::where("is_archive", false)->Accepted()->find($cart->product_id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product not found.',
                ], 404);
            }

            if ($product->stock < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient stock for purchase.',
                ], 422);
            }



            $cart->update(['quantity' => $validated['quantity']]);

            return response()->json([
                'success' => true,
                'message' => 'Cart item quantity updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }


    public function deleteItem(Request $request, Cart $cart)
    {
        try {
            $doctor = $request->user()->doctor;

            if ($cart->doctor_id !== $doctor->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized action.',
                ], 403);
            }

            $cart->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cart item deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }
}