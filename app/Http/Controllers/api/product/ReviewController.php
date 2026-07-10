<?php

namespace App\Http\Controllers\api\product;

use App\Http\Controllers\Controller;
use App\Http\Requests\reviewRequest;
use App\Models\ProductPart\Product;
use App\Models\ProductPart\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, Product $product)
    {
        try {
            $doctor = $request->user()->doctor;

            if (! $doctor) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor authentication required.',
                ], 403);
            }





            return response()->json([
                'success' => true,
                'message' => 'Review retrieved successfully.',
                'data' => $doctor->reviews()->where("product_id",$product->id)->
                with("doctor:id,user_table_id,profile_image_url","doctor.allUser:id,fullname")->get(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }
    public function add(reviewRequest $request, Product $product)
    {
        try {
            $doctor = $request->user()->doctor;

            if (! $doctor) {
                return response()->json([
                    'success' => false,
                    'error' => 'Doctor authentication required.',
                ], 403);
            }

            if ($product->reviews()->where('doctor_id', $doctor->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'You already submitted a review for this product.',
                ], 422);
            }

            $review = $product->reviews()->create([
                'doctor_id' => $doctor->id,
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
            ]);

            $review->load('doctor:id,user_table_id,profile_image_url');

            return response()->json([
                'success' => true,
                'message' => 'Review added successfully.',
                'data' => $review,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request, Review $review)
    {
        try {
            $doctor = $request->user()->doctor;

            if (! $doctor || $review->doctor_id !== $doctor->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized action.',
                ], 403);
            }

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }
}