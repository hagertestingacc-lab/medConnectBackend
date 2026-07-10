<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\AllUserPart\AllUser;
use App\Services\Doctor\DoctorActivities;
use Illuminate\Http\Request;

class AllUserController extends Controller
{

    public function getAllUser(Request $request, $page = 1, $per_page, $filterByRole = "all")
    {
        try {

            $allUsers = AllUser::when($filterByRole != "all", function ($query) use ($filterByRole) {
                return $query->where("role", $filterByRole);
            })->where("role", '!=', "admin")->with("doctor",
            "supplier")->paginate($per_page, ["*"], "page", $page);




            return response()->json([
                "success" => true,
                "message" => "users returned successfully",
                "data" => $allUsers->items(),
                "pagination" => [
                    "current_page" => $allUsers->currentPage(),
                    "last_page" => $allUsers->lastPage(),
                    "per_page" => $allUsers->perPage(),
                    "total" => $allUsers->total(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }
    public function getdoctor(Request $request)
    {
        try {


            $user = $request->user();
            $user->load("doctor", "doctor.doctorLicense:license_number,issue_authority,authority_type");


            return response()->json([
                "success" => true,
                "message" => "doctor returned successfully",
                "data" => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }
    public function updateDoctorAddress(Request $request)
    {
        $validated = $request->validate([
            "address" => "string|required"
        ]);
        try {
            $user = $request->user();
            $user->update(["address" => $validated["address"]]);

            return response()->json([
                "success" => true,
                "message" => "address updated successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured " . $e->getMessage(),
            ], 500);
        }
    }

    public function updateDoctorImage(Request $request)
    {
        $validated = $request->validate([
            'profile_image_url' => 'required|image'
        ]);

        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found.'
                ], 404);
            }

            $publicIdToUse = null;
            if ($doctor->cloudinary_profile_img_id && $doctor->cloudinary_profile_img_id !== DoctorActivities::DEFAULT_PROFILE_IMAGE_PUBLIC_ID) {
                $publicIdToUse = $doctor->cloudinary_profile_img_id;
            }

            $uploadedImage = DoctorActivities::uploadingProfileImg($request->file('profile_image_url'), $publicIdToUse);

            if (!$uploadedImage['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload image',
                    'error' => $uploadedImage['error'] ?? 'Upload error'
                ], 500);
            }

            $doctor->update([
                'profile_image_url' => $uploadedImage['data']['url'],
                'cloudinary_profile_img_id' => $uploadedImage['data']['public_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Doctor profile image updated successfully.',
                'data' => [
                    'profile_image_url' => $uploadedImage['data']['url'],
                    'cloudinary_profile_img_id' => $uploadedImage['data']['public_id'],
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteDoctorImage(Request $request)
    {
        try {
            $user = $request->user();
            $doctor = $user->doctor;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found.'
                ], 404);
            }

            $currentPublicId = $doctor->cloudinary_profile_img_id;
            if ($currentPublicId && $currentPublicId !== DoctorActivities::DEFAULT_PROFILE_IMAGE_PUBLIC_ID) {
                DoctorActivities::deleteProfileImg($currentPublicId);
            }

            $doctor->update([
                'profile_image_url' => DoctorActivities::DEFAULT_PROFILE_IMAGE_URL,
                'cloudinary_profile_img_id' => DoctorActivities::DEFAULT_PROFILE_IMAGE_PUBLIC_ID,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Doctor profile image deleted and reset to default successfully.',
                'data' => [
                    'profile_image_url' => DoctorActivities::DEFAULT_PROFILE_IMAGE_URL,
                    'cloudinary_profile_img_id' => DoctorActivities::DEFAULT_PROFILE_IMAGE_PUBLIC_ID,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An issue occured ' . $e->getMessage(),
            ], 500);
        }
    }
}
